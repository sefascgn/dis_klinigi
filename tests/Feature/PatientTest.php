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
        $response = $this->actingAs($this->secretary())->post('/patients', $this->validData());

        $patient = Patient::firstOrFail();
        $response->assertRedirect(route('patients.show', $patient));
        $this->assertSame('Ali Veli', $patient->full_name);
    }

    public function test_patient_validation(): void
    {
        $this->actingAs($this->secretary())
            ->post('/patients', $this->validData(['first_name' => '', 'national_id' => '123', 'phone' => 'abc', 'birth_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['first_name', 'national_id', 'phone', 'birth_date']);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_national_id_must_be_unique_but_ignores_self_on_update(): void
    {
        $patient = Patient::factory()->create(['national_id' => '12345678901']);
        $user = $this->secretary();

        $this->actingAs($user)->post('/patients', $this->validData())
            ->assertSessionHasErrors('national_id');

        $this->actingAs($user)->put("/patients/{$patient->id}", $this->validData(['first_name' => 'Yeni']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Yeni', $patient->fresh()->first_name);
    }

    public function test_patients_can_be_searched(): void
    {
        Patient::factory()->create(['first_name' => 'Zeynep', 'last_name' => 'Kaya']);
        Patient::factory()->create(['first_name' => 'Mehmet', 'last_name' => 'Demir']);

        $this->actingAs($this->secretary())->get('/patients?q=zeynep ka')
            ->assertOk()
            ->assertSee('Zeynep Kaya')
            ->assertDontSee('Mehmet Demir');
    }

    public function test_patient_search_endpoint_returns_json(): void
    {
        Patient::factory()->create(['first_name' => 'Zeynep', 'last_name' => 'Kaya', 'phone' => '05321234567']);

        $this->actingAs($this->secretary())->getJson('/patients/search?q=0532')
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

        $this->actingAs($this->secretary())->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertSee('Kontrol randevusu')
            ->assertSee('1.800,00 ₺')
            ->assertSee('Hesap özeti');
    }

    public function test_doctor_does_not_see_financial_summary(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->doctorUser())->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertDontSee('Hesap özeti');
    }

    public function test_only_admin_can_delete_and_history_is_kept(): void
    {
        $patient = Patient::factory()->create();
        Payment::factory()->for($patient)->create();

        $this->actingAs($this->secretary())->delete("/patients/{$patient->id}")->assertForbidden();

        $this->actingAs($this->admin())->delete("/patients/{$patient->id}")->assertRedirect('/patients');

        $this->assertSoftDeleted($patient);
        $this->assertDatabaseCount('payments', 1);
        $this->assertNotNull(Payment::first()->patient, 'Arşivlenen hasta ödeme kaydında görünmeye devam etmeli.');
    }
}
