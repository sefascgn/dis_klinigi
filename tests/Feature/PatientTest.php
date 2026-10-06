<?php

namespace Tests\Feature;

use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'national_id' => '12345678901',
            'birth_date' => '1990-05-10',
            'gender' => 'male',
            'phone' => '0555 111 22 33',
            'email' => 'ali@example.com',
            'allergies' => 'Penisilin',
        ], $overrides);
    }

    public function test_secretary_can_create_patient(): void
    {
        $response = $this->actingAs($this->secretary())->post('/hastalar', $this->validData());

        $patient = Patient::firstOrFail();
        $response->assertRedirect(route('patients.show', $patient));
        $this->assertSame('Ali Veli', $patient->full_name);
    }

    public function test_patient_validation(): void
    {
        $this->actingAs($this->secretary())
            ->post('/hastalar', $this->validData(['first_name' => '', 'national_id' => '123', 'phone' => 'abc', 'birth_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['first_name', 'national_id', 'phone', 'birth_date']);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_national_id_must_be_unique_but_ignores_self_on_update(): void
    {
        $patient = Patient::factory()->create(['national_id' => '12345678901']);
        $user = $this->secretary();

        $this->actingAs($user)->post('/hastalar', $this->validData())
            ->assertSessionHasErrors('national_id');

        $this->actingAs($user)->put("/hastalar/{$patient->id}", $this->validData(['first_name' => 'Yeni']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Yeni', $patient->fresh()->first_name);
    }

    public function test_patients_can_be_searched(): void
    {
        Patient::factory()->create(['first_name' => 'Zeynep', 'last_name' => 'Kaya']);
        Patient::factory()->create(['first_name' => 'Mehmet', 'last_name' => 'Demir']);

        $this->actingAs($this->secretary())->get('/hastalar?q=zeynep ka')
            ->assertOk()
            ->assertSee('Zeynep Kaya')
            ->assertDontSee('Mehmet Demir');
    }

    public function test_patient_search_endpoint_returns_json(): void
    {
        Patient::factory()->create(['first_name' => 'Zeynep', 'last_name' => 'Kaya', 'phone' => '05321234567']);

        $this->actingAs($this->secretary())->getJson('/hastalar/ara?q=0532')
            ->assertOk()
            ->assertJsonPath('0.name', 'Zeynep Kaya');
    }

    public function test_detail_page_shows_history_and_balance(): void
    {
        $patient = Patient::factory()->create();
        Appointment::factory()->for($patient)->create(['notes' => 'Kontrol randevusu']);
        Treatment::factory()->for($patient)->create(['price' => 3000]);
        Treatment::factory()->for($patient)->create(['price' => 1000, 'status' => TreatmentStatus::Cancelled]);
        Payment::factory()->for($patient)->create(['amount' => 1200]);

        $this->assertSame(1800.0, $patient->balance());

        $this->actingAs($this->secretary())->get("/hastalar/{$patient->id}")
            ->assertOk()
            ->assertSee('Kontrol randevusu')
            ->assertSee('1.800,00 ₺')
            ->assertSee('Hesap özeti');
    }

    public function test_doctor_does_not_see_financial_summary(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->doctorUserOf($patient))->get("/hastalar/{$patient->id}")
            ->assertOk()
            ->assertDontSee('Hesap özeti');
    }

    public function test_only_admin_can_delete_and_history_is_kept(): void
    {
        $patient = Patient::factory()->create();
        Payment::factory()->for($patient)->create();

        $this->actingAs($this->secretary())->delete("/hastalar/{$patient->id}")->assertForbidden();

        $this->actingAs($this->admin())->delete("/hastalar/{$patient->id}")->assertRedirect('/hastalar');

        $this->assertSoftDeleted($patient);
        $this->assertDatabaseCount('payments', 1);
        $this->assertNotNull(Payment::first()->patient, 'Arşivlenen hasta ödeme kaydında görünmeye devam etmeli.');
    }

    public function test_planned_and_cancelled_treatments_are_not_debt(): void
    {
        $patient = Patient::factory()->create();
        Treatment::factory()->for($patient)->create(['status' => TreatmentStatus::Planned, 'price' => 10000]);
        Treatment::factory()->for($patient)->create(['status' => TreatmentStatus::Cancelled, 'price' => 5000]);
        Treatment::factory()->for($patient)->create(['status' => TreatmentStatus::InProgress, 'price' => 2000]);
        Treatment::factory()->for($patient)->create(['status' => TreatmentStatus::Completed, 'price' => 1000]);
        Payment::factory()->for($patient)->create(['amount' => 500]);

        $this->assertSame(2500.0, $patient->balance());
        $this->assertSame(2500.0, Patient::withBalance()->find($patient->id)->balance());

        // Yalnızca teklif aşamasındaki hasta borçlu listesine düşmez.
        $quoteOnly = Patient::factory()->create();
        Treatment::factory()->for($quoteOnly)->create(['status' => TreatmentStatus::Planned, 'price' => 8000]);

        $debtorIds = Patient::withBalance()->debtors()->pluck('id');
        $this->assertTrue($debtorIds->contains($patient->id));
        $this->assertFalse($debtorIds->contains($quoteOnly->id));
    }

    public function test_doctor_sees_only_own_patients(): void
    {
        $user = $this->doctorUser();
        $viaAppointment = Patient::factory()->create(['first_name' => 'Randevulu']);
        $viaTreatment = Patient::factory()->create(['first_name' => 'Tedavili']);
        $other = Patient::factory()->create(['first_name' => 'Yabanci']);
        Appointment::factory()->create(['patient_id' => $viaAppointment->id, 'doctor_id' => $user->doctor->id]);
        Treatment::factory()->for($viaTreatment)->create(['doctor_id' => $user->doctor->id]);
        Appointment::factory()->create(['patient_id' => $other->id]);

        $this->actingAs($user)->get('/hastalar')
            ->assertOk()
            ->assertSee('Randevulu')
            ->assertSee('Tedavili')
            ->assertDontSee('Yabanci');

        $this->actingAs($user)->get("/hastalar/{$viaAppointment->id}")->assertOk();
        $this->actingAs($user)->get("/hastalar/{$other->id}")->assertForbidden();
        $this->actingAs($user)->get("/hastalar/{$other->id}/duzenle")->assertForbidden();
        $this->actingAs($user)->get("/hastalar/{$other->id}/yazdir/tedavi-plani")->assertForbidden();

        $names = collect($this->actingAs($user)->getJson('/hastalar/ara?q=Yabanci')->json())->pluck('name');
        $this->assertCount(0, $names);

        // Sekreter tüm hastaları görür.
        $this->actingAs($this->secretary())->get("/hastalar/{$other->id}")->assertOk();
    }

    public function test_doctor_can_open_patient_they_created(): void
    {
        $user = $this->doctorUser();

        $this->actingAs($user)->post('/hastalar', $this->validData())->assertRedirect();

        $patient = Patient::firstOrFail();
        $this->assertSame($user->id, $patient->created_by);
        $this->actingAs($user)->get("/hastalar/{$patient->id}")->assertOk();
    }
}
