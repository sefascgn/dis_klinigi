<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\MedicalCondition;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Support\Phone;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Anamnez, borçlu / kontrol listeleri, WhatsApp hatırlatma, yazdırma ve raporlar.
 */
class ClinicToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_conditions_are_saved_and_shown_as_alert(): void
    {
        $patient = Patient::factory()->create(['medical_conditions' => [], 'allergies' => null]);
        $user = $this->secretary();

        $this->actingAs($user)->put("/hastalar/{$patient->id}", [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'phone' => $patient->phone,
            'medical_conditions' => ['blood_thinner', 'diabetes'],
        ])->assertSessionHasNoErrors();

        $patient->refresh();
        $this->assertTrue($patient->medical_conditions->contains(MedicalCondition::BloodThinner));
        $this->actingAs($user)->get("/hastalar/{$patient->id}")->assertSee('Sağlık uyarısı')->assertSee('Kan sulandırıcı kullanıyor');

        // Tüm kutular kaldırılınca liste boşalır
        $this->actingAs($user)->put("/hastalar/{$patient->id}", [
            'first_name' => $patient->first_name, 'last_name' => $patient->last_name, 'phone' => $patient->phone,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($patient->fresh()->medical_conditions->isEmpty());
    }

    public function test_invalid_medical_condition_is_rejected(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->secretary())->put("/hastalar/{$patient->id}", [
            'first_name' => 'A', 'last_name' => 'B', 'phone' => '05551112233', 'medical_conditions' => ['vampirism'],
        ])->assertSessionHasErrors('medical_conditions.0');
    }

    public function test_debtors_filter_lists_only_patients_with_balance(): void
    {
        $debtor = Patient::factory()->create(['first_name' => 'Borçlu']);
        Treatment::factory()->for($debtor)->create(['price' => 1000]);
        $paid = Patient::factory()->create(['first_name' => 'Ödemiş']);
        Treatment::factory()->for($paid)->create(['price' => 500]);
        Payment::factory()->for($paid)->create(['amount' => 500]);

        $this->actingAs($this->secretary())->get('/hastalar?filter=debtors')
            ->assertOk()
            ->assertSee('Borçlu')
            ->assertDontSee('Ödemiş');

        $this->actingAs($this->doctorUser())->get('/hastalar?filter=debtors')->assertForbidden();
    }

    public function test_recall_filter_lists_patients_due_for_checkup(): void
    {
        $due = Patient::factory()->create(['first_name' => 'Kontrolgelen']);
        Appointment::factory()->for($due)->at(Carbon::now()->subMonths(8))->status(AppointmentStatus::Completed)->create();

        $recent = Patient::factory()->create(['first_name' => 'Yeniziyaret']);
        Appointment::factory()->for($recent)->at(Carbon::now()->subMonth())->status(AppointmentStatus::Completed)->create();

        $booked = Patient::factory()->create(['first_name' => 'Randevulu']);
        Appointment::factory()->for($booked)->at(Carbon::now()->subMonths(8))->status(AppointmentStatus::Completed)->create();
        Appointment::factory()->for($booked)->at(Carbon::now()->addWeek())->create();

        $this->actingAs($this->secretary())->get('/hastalar?filter=recall')
            ->assertOk()
            ->assertSee('Kontrolgelen')
            ->assertDontSee('Yeniziyaret')
            ->assertDontSee('Randevulu')
            ->assertSee('https://wa.me/', false);
    }

    public function test_whatsapp_numbers_are_normalized(): void
    {
        $this->assertSame('905321234567', WhatsApp::normalize('0532 123 45 67'));
        $this->assertSame('905321234567', WhatsApp::normalize('+90 (532) 123-45-67'));
        $this->assertSame('905321234567', WhatsApp::normalize('5321234567'));
        $this->assertNull(WhatsApp::normalize('12345'));
        $this->assertNull(WhatsApp::link(null, 'x'));
    }

    public function test_appointment_menu_offers_whatsapp_reminder_for_upcoming(): void
    {
        $appointment = Appointment::factory()->at(Carbon::tomorrow()->setTime(10, 0))->create();
        $appointment->patient->update(['phone' => '05321234567']);

        $this->actingAs($this->secretary())->get('/randevular?view=list')
            ->assertSee('https://wa.me/905321234567', false)
            ->assertSee('WhatsApp ile hatırlat');
    }

    public function test_patient_and_doctor_pages_render_with_upcoming_appointments(): void
    {
        // Regresyon: randevu menüsündeki WhatsApp bağlantısı ilişkileri lazy load etmemeli.
        $appointment = Appointment::factory()->at(Carbon::tomorrow()->setTime(10, 0))->create();
        $user = $this->secretary();

        $this->actingAs($user)->get("/hastalar/{$appointment->patient_id}")->assertOk()->assertSee('WhatsApp ile hatırlat');
        $this->actingAs($user)->get("/doktorlar/{$appointment->doctor_id}")->assertOk()->assertSee('WhatsApp ile hatırlat');
    }

    public function test_treatment_plan_print_lists_planned_treatments_with_total(): void
    {
        $patient = Patient::factory()->create();
        Treatment::factory()->for($patient)->create(['status' => TreatmentStatus::Planned, 'price' => 6000]);
        Treatment::factory()->for($patient)->create(['status' => TreatmentStatus::InProgress, 'price' => 4000]);
        Treatment::factory()->for($patient)->create(['status' => TreatmentStatus::Completed, 'price' => 999]);

        $this->actingAs($this->doctorUserOf($patient))->get("/hastalar/{$patient->id}/yazdir/tedavi-plani")
            ->assertOk()
            ->assertSee('Tedavi Planı ve Fiyat Teklifi')
            ->assertSee('10.000,00 ₺')
            ->assertDontSee('999,00 ₺');
    }

    public function test_statement_and_receipt_require_payment_access(): void
    {
        $patient = Patient::factory()->create();
        Treatment::factory()->for($patient)->create(['price' => 3000, 'treatment_date' => today()->subDays(3)]);
        $payment = Payment::factory()->for($patient)->create(['amount' => 1000, 'paid_at' => today()]);

        $this->actingAs($this->secretary())->get("/hastalar/{$patient->id}/yazdir/hesap-ekstresi")
            ->assertOk()
            ->assertSee('Kalan borç')
            ->assertSee('2.000,00 ₺');

        $this->actingAs($this->secretary())->get("/odemeler/{$payment->id}/makbuz")
            ->assertOk()
            ->assertSee('Tahsilat Makbuzu')
            ->assertSee('1.000,00 ₺');

        $doctor = $this->doctorUser();
        $this->actingAs($doctor)->get("/hastalar/{$patient->id}/yazdir/hesap-ekstresi")->assertForbidden();
        $this->actingAs($doctor)->get("/odemeler/{$payment->id}/makbuz")->assertForbidden();
    }

    public function test_reports_show_summary_for_admin_only(): void
    {
        $patient = Patient::factory()->create();
        Treatment::factory()->for($patient)->create(['price' => 5000, 'status' => TreatmentStatus::Completed, 'treatment_date' => today()]);
        Payment::factory()->for($patient)->create(['amount' => 2000, 'paid_at' => today()]);

        $this->actingAs($this->admin())->get('/raporlar')
            ->assertOk()
            ->assertViewHas('totals', fn ($t) => $t['collected']['value'] === 2000.0 && $t['revenue']['value'] === 5000.0)
            ->assertViewHas('operations', fn ($o) => $o['receivable'] === 3000.0 && $o['debtors'] === 1)
            ->assertSee('Aylık gelen ve giden para');

        $this->actingAs($this->secretary())->get('/raporlar')->assertForbidden();
        $this->actingAs($this->doctorUser())->get('/raporlar')->assertForbidden();
    }

    public function test_phone_is_formatted_and_national_id_masked_in_list(): void
    {
        $this->assertSame('0532 123 45 67', Phone::format('05321234567'));
        $this->assertSame('0532 123 45 67', Phone::format('+90 532 123 4567'));
        $this->assertSame('0532 123 45 67', Phone::format('5321234567'));
        $this->assertSame('+44 20 7946 0958', Phone::format('+44 20 7946 0958'), 'Tanınmayan numara olduğu gibi kalır.');
        $this->assertNull(Phone::format(null));

        $patient = Patient::factory()->create(['phone' => '05321234567', 'national_id' => '12345678901']);

        $this->actingAs($this->secretary())->get('/hastalar')
            ->assertSee('0532 123 45 67')
            ->assertSee('123******01')
            ->assertDontSee('12345678901');

        // Kartta tam numara görünür.
        $this->actingAs($this->secretary())->get("/hastalar/{$patient->id}")->assertSee('12345678901');
    }

    public function test_error_pages_are_turkish(): void
    {
        $this->actingAs($this->secretary())->get('/olmayan-sayfa')
            ->assertNotFound()
            ->assertSee('Sayfa bulunamadı');

        $this->actingAs($this->secretary())->get('/raporlar')
            ->assertForbidden()
            ->assertSee('Bu sayfaya erişim yetkiniz yok');
    }
}
