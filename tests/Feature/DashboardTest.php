<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_daily_stats_and_revenue(): void
    {
        Carbon::setTestNow(today()->setTime(8, 0));

        Appointment::factory()->at(today()->setTime(10, 0))->create();
        Appointment::factory()->at(today()->setTime(11, 0))->status(AppointmentStatus::Completed)->create();
        Appointment::factory()->at(today()->setTime(12, 0))->status(AppointmentStatus::Cancelled)->create();
        $upcoming = Appointment::factory()->at(today()->addDays(2)->setTime(9, 0))->create();
        Payment::factory()->create(['amount' => 750, 'paid_at' => today()]);

        $response = $this->actingAs($this->secretary())->get('/panel');

        $response->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['today'] === 2 && $stats['completed_today'] === 1 && $stats['pending'] === 2)
            ->assertViewHas('revenue', fn ($revenue) => $revenue['daily'] === 750.0)
            ->assertSee($upcoming->patient->full_name)
            ->assertSee('750,00 ₺');
    }

    public function test_doctor_dashboard_is_scoped_and_hides_revenue(): void
    {
        $user = $this->doctorUser();
        Appointment::factory()->for($user->doctor)->at(today()->setTime(23, 0))->create();
        Appointment::factory()->at(today()->setTime(23, 0))->create();

        $this->actingAs($user)->get('/panel')
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['today'] === 1)
            ->assertViewHas('revenue', null)
            ->assertDontSee('Bugünkü gelir');
    }
}
