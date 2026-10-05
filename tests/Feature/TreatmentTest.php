<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\TreatmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'treatment_type_id' => TreatmentType::factory()->create()->id,
            'tooth_number' => 36,
            'price' => '2500.50',
            'status' => 'completed',
            'treatment_date' => today()->toDateString(),
            'notes' => 'Kompozit dolgu',
        ], $overrides);
    }

    public function test_admin_can_record_treatment(): void
    {
        $data = $this->payload();

        $this->actingAs($this->admin())->post('/treatments', $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('patients.show', $data['patient_id']));

        $this->assertDatabaseHas('treatments', ['patient_id' => $data['patient_id'], 'tooth_number' => 36, 'price' => 2500.50]);
    }

    public function test_doctor_records_treatment_in_own_name(): void
    {
        $user = $this->doctorUser();
        $otherDoctor = Doctor::factory()->create();

        $this->actingAs($user)->post('/treatments', $this->payload(['doctor_id' => $otherDoctor->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($user->doctor->id, Treatment::firstOrFail()->doctor_id);
    }

    public function test_doctor_can_edit_only_own_treatments(): void
    {
        $user = $this->doctorUser();
        $own = Treatment::factory()->for($user->doctor)->create();
        $other = Treatment::factory()->create();

        $this->actingAs($user)->get("/treatments/{$own->id}/edit")->assertOk();
        $this->actingAs($user)->get("/treatments/{$other->id}/edit")->assertForbidden();
        $this->actingAs($user)->delete("/treatments/{$own->id}")->assertForbidden();
    }

    public function test_secretary_cannot_record_treatment(): void
    {
        $this->actingAs($this->secretary())->post('/treatments', $this->payload())->assertForbidden();
    }

    public function test_treatment_validation(): void
    {
        $this->actingAs($this->admin())->post('/treatments', $this->payload([
            'tooth_number' => 19,
            'price' => -5,
            'status' => 'unknown',
        ]))->assertSessionHasErrors(['tooth_number', 'price', 'status']);
    }

    public function test_appointment_must_belong_to_selected_patient(): void
    {
        $appointment = Appointment::factory()->status(AppointmentStatus::Completed)->create();

        $this->actingAs($this->admin())->post('/treatments', $this->payload(['appointment_id' => $appointment->id]))
            ->assertSessionHasErrors('appointment_id');

        $this->actingAs($this->admin())->post('/treatments', $this->payload([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
        ]))->assertSessionHasNoErrors();
    }

    public function test_create_form_is_prefilled_from_appointment(): void
    {
        $appointment = Appointment::factory()->status(AppointmentStatus::Completed)->create();

        $this->actingAs($this->admin())->get("/treatments/create?appointment_id={$appointment->id}")
            ->assertOk()
            ->assertSee($appointment->patient->full_name)
            ->assertSee('name="appointment_id" value="'.$appointment->id.'"', false);
    }

    public function test_used_treatment_type_cannot_be_deleted(): void
    {
        $treatment = Treatment::factory()->create();

        $this->actingAs($this->admin())->delete("/treatment-types/{$treatment->treatment_type_id}")->assertSessionHas('error');
        $this->assertModelExists($treatment->treatmentType);

        $unused = TreatmentType::factory()->create();
        $this->actingAs($this->admin())->delete("/treatment-types/{$unused->id}")->assertSessionHas('success');
        $this->assertModelMissing($unused);
    }

    public function test_admin_manages_treatment_types(): void
    {
        $this->actingAs($this->admin())->post('/treatment-types', [
            'name' => 'Kanal tedavisi', 'default_price' => 6000, 'is_active' => '1',
        ])->assertRedirect('/treatment-types');

        $this->actingAs($this->admin())->post('/treatment-types', ['name' => 'Kanal tedavisi', 'default_price' => 1])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->secretary())->post('/treatment-types', ['name' => 'X', 'default_price' => 1])
            ->assertForbidden();
    }
}
