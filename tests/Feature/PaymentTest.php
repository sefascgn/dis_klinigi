<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_take_payment(): void
    {
        $patient = Patient::factory()->create();
        Treatment::factory()->for($patient)->create(['price' => 5000]);
        $user = $this->secretary();

        $this->actingAs($user)->post('/odemeler', [
            'patient_id' => $patient->id,
            'amount' => '2000',
            'method' => 'credit_card',
            'paid_at' => today()->toDateString(),
            'notes' => '1. taksit',
        ])->assertSessionHasNoErrors()->assertRedirect(route('patients.show', $patient));

        $payment = Payment::firstOrFail();
        $this->assertSame($user->id, $payment->received_by);
        $this->assertSame(3000.0, $patient->balance());
    }

    public function test_payment_validation(): void
    {
        $this->actingAs($this->secretary())->post('/odemeler', [
            'patient_id' => Patient::factory()->create()->id,
            'amount' => 0,
            'method' => 'bitcoin',
            'paid_at' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['amount', 'method', 'paid_at']);
    }

    public function test_doctor_cannot_access_payments(): void
    {
        $payment = Payment::factory()->create();
        $user = $this->doctorUser();

        $this->actingAs($user)->get('/odemeler')->assertForbidden();
        $this->actingAs($user)->post('/odemeler', [])->assertForbidden();
        $this->actingAs($user)->get("/odemeler/{$payment->id}/duzenle")->assertForbidden();
    }

    public function test_only_admin_can_edit_or_delete_payment(): void
    {
        $payment = Payment::factory()->create();

        $this->actingAs($this->secretary())->get("/odemeler/{$payment->id}/duzenle")->assertForbidden();
        $this->actingAs($this->secretary())->delete("/odemeler/{$payment->id}")->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->delete("/odemeler/{$payment->id}")->assertRedirect();

        // Mali kayıt kalıcı silinmez; kimin sildiği tutulur ve bakiyeye girmez.
        $this->assertSoftDeleted($payment);
        $this->assertSame($admin->id, $payment->fresh()->deleted_by);
        $this->assertSame(0.0, $payment->patient->totalPaid());
    }

    public function test_index_filters_by_date_range_and_shows_total(): void
    {
        Payment::factory()->create(['amount' => 1000, 'paid_at' => today()]);
        Payment::factory()->create(['amount' => 500, 'paid_at' => today()]);
        Payment::factory()->create(['amount' => 9999, 'paid_at' => today()->subMonths(2)]);

        $this->actingAs($this->secretary())
            ->get('/odemeler?from='.today()->toDateString().'&to='.today()->toDateString())
            ->assertOk()
            ->assertSee('1.500,00 ₺')
            ->assertDontSee('9.999,00 ₺');
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $this->actingAs($this->secretary())
            ->from('/odemeler')
            ->get('/odemeler?from=2026-05-10&to=2026-05-01')
            ->assertSessionHasErrors('to');
    }
}
