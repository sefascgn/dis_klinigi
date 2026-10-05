<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_doctor_linked_to_doctor_account(): void
    {
        $account = User::factory()->create(['role' => 'doctor']);

        $this->actingAs($this->admin())->post('/doctors', [
            'title' => 'Uzm. Dt.',
            'first_name' => 'Can',
            'last_name' => 'Öz',
            'specialty' => 'Ortodonti',
            'user_id' => $account->id,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $doctor = Doctor::firstOrFail();
        $this->assertSame('Uzm. Dt. Can Öz', $doctor->full_name);
        $this->assertTrue($doctor->user->is($account));
    }

    public function test_only_doctor_role_accounts_can_be_linked(): void
    {
        $secretary = $this->secretary();

        $this->actingAs($this->admin())->post('/doctors', [
            'first_name' => 'Can', 'last_name' => 'Öz', 'user_id' => $secretary->id,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_secretary_cannot_manage_doctors(): void
    {
        $doctor = Doctor::factory()->create();
        $user = $this->secretary();

        $this->actingAs($user)->post('/doctors', ['first_name' => 'A', 'last_name' => 'B'])->assertForbidden();
        $this->actingAs($user)->put("/doctors/{$doctor->id}", ['first_name' => 'A', 'last_name' => 'B'])->assertForbidden();
        $this->actingAs($user)->delete("/doctors/{$doctor->id}")->assertForbidden();
    }

    public function test_doctor_page_lists_appointments(): void
    {
        $doctor = Doctor::factory()->create();
        $appointment = Appointment::factory()->for($doctor)->create();

        $this->actingAs($this->secretary())->get("/doctors/{$doctor->id}")
            ->assertOk()
            ->assertSee($appointment->patient->full_name);
    }

    public function test_doctor_with_upcoming_appointments_cannot_be_deleted(): void
    {
        $doctor = Doctor::factory()->create();
        Appointment::factory()->for($doctor)->at(Carbon::tomorrow()->setTime(10, 0))->create();

        $this->actingAs($this->admin())->delete("/doctors/{$doctor->id}")->assertSessionHas('error');
        $this->assertNotSoftDeleted($doctor);
    }

    public function test_deleted_doctor_keeps_past_appointments(): void
    {
        $doctor = Doctor::factory()->create();
        Appointment::factory()->for($doctor)->at(Carbon::now()->subWeek())->status(AppointmentStatus::Completed)->create();

        $this->actingAs($this->admin())->delete("/doctors/{$doctor->id}")->assertRedirect('/doctors');

        $this->assertSoftDeleted($doctor);
        $this->assertNotNull(Appointment::first()->doctor);
    }
}
