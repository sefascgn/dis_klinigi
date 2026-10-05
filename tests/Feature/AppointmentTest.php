<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Doctor $doctor, Patient $patient, Carbon $start, int $duration = 30): array
    {
        return [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $start->format('Y-m-d\TH:i'),
            'duration' => $duration,
            'notes' => 'Diş ağrısı',
        ];
    }

    public function test_secretary_can_create_appointment(): void
    {
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $start = Carbon::tomorrow()->setTime(10, 0);
        $user = $this->secretary();

        $this->actingAs($user)->post('/appointments', $this->payload($doctor, $patient, $start, 45))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('appointments.index', ['date' => $start->toDateString()]));

        $appointment = Appointment::firstOrFail();
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertTrue($appointment->ends_at->eq($start->copy()->addMinutes(45)));
        $this->assertSame($user->id, $appointment->created_by);
    }

    public function test_overlapping_appointment_for_same_doctor_is_rejected(): void
    {
        $doctor = Doctor::factory()->create();
        $start = Carbon::tomorrow()->setTime(10, 0);
        Appointment::factory()->for($doctor)->at($start, 60)->create();

        $this->actingAs($this->secretary())
            ->post('/appointments', $this->payload($doctor, Patient::factory()->create(), $start->copy()->addMinutes(30)))
            ->assertSessionHasErrors('starts_at');

        // Bitişik randevu ve farklı doktor serbest
        $this->actingAs($this->secretary())
            ->post('/appointments', $this->payload($doctor, Patient::factory()->create(), $start->copy()->addHour()))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->secretary())
            ->post('/appointments', $this->payload(Doctor::factory()->create(), Patient::factory()->create(), $start))
            ->assertSessionHasNoErrors();
    }

    public function test_cancelled_appointment_does_not_block_slot(): void
    {
        $doctor = Doctor::factory()->create();
        $start = Carbon::tomorrow()->setTime(10, 0);
        Appointment::factory()->for($doctor)->at($start)->status(AppointmentStatus::Cancelled)->create();

        $this->actingAs($this->secretary())
            ->post('/appointments', $this->payload($doctor, Patient::factory()->create(), $start))
            ->assertSessionHasNoErrors();
    }

    public function test_appointment_validation(): void
    {
        $this->actingAs($this->secretary())->post('/appointments', [
            'patient_id' => 999,
            'doctor_id' => Doctor::factory()->inactive()->create()->id,
            'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'duration' => 7,
        ])->assertSessionHasErrors(['patient_id', 'doctor_id', 'starts_at', 'duration']);
    }

    public function test_appointment_can_be_updated_without_conflicting_with_itself(): void
    {
        $appointment = Appointment::factory()->at(Carbon::tomorrow()->setTime(10, 0))->create();

        $this->actingAs($this->secretary())->put("/appointments/{$appointment->id}", [
            ...$this->payload($appointment->doctor, $appointment->patient, Carbon::tomorrow()->setTime(10, 15)),
            'status' => 'confirmed',
        ])->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame('10:15', $appointment->starts_at->format('H:i'));
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
    }

    public function test_cancel_requires_reason(): void
    {
        $appointment = Appointment::factory()->create();
        $user = $this->secretary();

        $this->actingAs($user)->patch("/appointments/{$appointment->id}/status", ['status' => 'cancelled'])
            ->assertSessionHasErrors('cancellation_reason');

        $this->actingAs($user)->patch("/appointments/{$appointment->id}/status", ['status' => 'cancelled', 'cancellation_reason' => 'Hasta aradı'])
            ->assertSessionHasNoErrors();

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertSame('Hasta aradı', $appointment->fresh()->cancellation_reason);
    }

    public function test_reopening_cancelled_appointment_checks_conflicts(): void
    {
        $start = Carbon::tomorrow()->setTime(10, 0);
        $cancelled = Appointment::factory()->at($start)->status(AppointmentStatus::Cancelled)->create();
        Appointment::factory()->for($cancelled->doctor)->at($start)->create();

        $this->actingAs($this->secretary())->patch("/appointments/{$cancelled->id}/status", ['status' => 'pending'])
            ->assertSessionHas('error');

        $this->assertSame(AppointmentStatus::Cancelled, $cancelled->fresh()->status);
    }

    public function test_doctor_sees_and_manages_only_own_appointments(): void
    {
        $user = $this->doctorUser();
        $date = Carbon::tomorrow()->setTime(10, 0);
        $own = Appointment::factory()->for($user->doctor)->at($date)->create();
        $other = Appointment::factory()->at($date)->create();

        $this->actingAs($user)->get('/appointments?date='.$date->toDateString())
            ->assertOk()
            ->assertSee($own->patient->full_name)
            ->assertDontSee($other->patient->full_name);

        $this->actingAs($user)->patch("/appointments/{$own->id}/status", ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->actingAs($user)->patch("/appointments/{$other->id}/status", ['status' => 'completed'])->assertForbidden();
        $this->actingAs($user)->put("/appointments/{$own->id}", [])->assertForbidden();

        $this->assertSame(AppointmentStatus::Completed, $own->fresh()->status);
    }

    public function test_index_filters_by_status_across_all_dates(): void
    {
        $pending = Appointment::factory()->at(Carbon::now()->addDays(3))->create();
        $cancelled = Appointment::factory()->at(Carbon::now()->addDays(4))->status(AppointmentStatus::Cancelled)->create();

        $this->actingAs($this->secretary())->get('/appointments?view=list&status=pending')
            ->assertOk()
            ->assertSee($pending->patient->full_name)
            ->assertDontSee($cancelled->patient->full_name);
    }

    public function test_day_view_shows_doctor_columns_and_appointments(): void
    {
        $doctor = Doctor::factory()->create(['first_name' => 'Kemal', 'last_name' => 'Sunal']);
        $date = Carbon::tomorrow();
        $appointment = Appointment::factory()->for($doctor)->at($date->copy()->setTime(14, 30))->create();
        $other = Appointment::factory()->at($date->copy()->addDay()->setTime(10, 0))->create();

        $this->actingAs($this->secretary())->get('/appointments?view=day&date='.$date->toDateString())
            ->assertOk()
            ->assertSee('Kemal Sunal')
            ->assertSee($appointment->patient->full_name)
            ->assertDontSee($other->patient->full_name)
            // Boş slota tıklayınca o doktor ve saat için randevu formu açılır
            ->assertSee(route('appointments.create', ['doctor_id' => $doctor->id, 'date' => $date->toDateString(), 'time' => '15:00']));
    }

    public function test_week_view_shows_only_that_weeks_appointments(): void
    {
        $monday = Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY);
        $sunday = Appointment::factory()->at($monday->copy()->addDays(6)->setTime(11, 0))->create();
        $nextMonday = Appointment::factory()->at($monday->copy()->addWeek()->setTime(11, 0))->create();

        $this->actingAs($this->secretary())->get('/appointments?view=week&date='.$monday->copy()->addDays(2)->toDateString())
            ->assertOk()
            ->assertViewHas('from', fn ($from) => $from->eq($monday))
            ->assertSee($sunday->patient->full_name)
            ->assertDontSee($nextMonday->patient->full_name);
    }

    public function test_month_view_groups_appointments_and_links_overflow_to_day(): void
    {
        $day = Carbon::now()->addMonth()->startOfMonth()->addDays(9);
        foreach ([9, 10, 11, 12] as $hour) {
            Appointment::factory()->at($day->copy()->setTime($hour, 0))->create();
        }

        $this->actingAs($this->secretary())->get('/appointments?view=month&date='.$day->toDateString())
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 4)
            ->assertSee('+1 randevu daha');
    }

    public function test_doctor_calendar_shows_only_own_column(): void
    {
        $user = $this->doctorUser();
        $other = Doctor::factory()->create(['first_name' => 'Başka', 'last_name' => 'Doktor']);

        $this->actingAs($user)->get('/appointments?view=day')
            ->assertOk()
            ->assertViewHas('columns', fn ($columns) => $columns->pluck('id')->all() === [$user->doctor->id])
            ->assertDontSee('Başka Doktor');
    }

    public function test_invalid_view_is_rejected(): void
    {
        $this->actingAs($this->secretary())->from('/dashboard')->get('/appointments?view=year')
            ->assertSessionHasErrors('view');
    }

    public function test_create_form_accepts_preselected_time(): void
    {
        $date = Carbon::tomorrow()->toDateString();

        $this->actingAs($this->secretary())->get("/appointments/create?date={$date}&time=14:30")
            ->assertOk()
            ->assertSee('value="'.$date.'T14:30"', false);
    }

    public function test_only_admin_can_delete_appointment(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($this->secretary())->delete("/appointments/{$appointment->id}")->assertForbidden();
        $this->actingAs($this->admin())->delete("/appointments/{$appointment->id}")->assertRedirect();

        $this->assertModelMissing($appointment);
    }
}
