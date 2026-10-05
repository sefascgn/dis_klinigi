<?php

namespace Tests\Feature;

use App\Enums\TreatmentStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Treatment;
use App\Services\FinanceReport;
use App\Support\ChartScale;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Giderler, gelir–gider hesapları ve finans grafikleri.
 */
class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_expense(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/expenses', [
            'category' => 'rent',
            'description' => 'Ekim kirası',
            'amount' => '18000',
            'method' => 'bank_transfer',
            'spent_at' => today()->toDateString(),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $expense = Expense::firstOrFail();
        $this->assertSame('18000.00', $expense->amount);
        $this->assertSame($admin->id, $expense->created_by);
    }

    public function test_expense_validation(): void
    {
        $this->actingAs($this->admin())->post('/expenses', [
            'category' => 'yacht',
            'description' => '',
            'amount' => 0,
            'method' => 'cash',
            'spent_at' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['category', 'description', 'amount', 'spent_at']);
    }

    public function test_expenses_are_admin_only(): void
    {
        $expense = Expense::factory()->create();

        foreach ([$this->secretary(), $this->doctorUser()] as $user) {
            $this->actingAs($user)->get('/expenses')->assertForbidden();
            $this->actingAs($user)->post('/expenses', [])->assertForbidden();
            $this->actingAs($user)->delete("/expenses/{$expense->id}")->assertForbidden();
        }
    }

    public function test_expense_can_be_copied_with_todays_date(): void
    {
        $expense = Expense::factory()->create(['description' => 'Aylık kira', 'spent_at' => today()->subMonth()]);

        $this->actingAs($this->admin())->get("/expenses/create?copy={$expense->id}")
            ->assertOk()
            ->assertSee('value="Aylık kira"', false)
            ->assertSee('value="'.today()->toDateString().'"', false);
    }

    public function test_index_shows_period_total_and_categories(): void
    {
        Expense::factory()->create(['category' => 'rent', 'amount' => 10000, 'spent_at' => today()]);
        Expense::factory()->create(['category' => 'lab', 'amount' => 2500, 'spent_at' => today()]);
        Expense::factory()->create(['category' => 'lab', 'amount' => 99999, 'spent_at' => today()->subYear()]);

        $this->actingAs($this->admin())->get('/expenses?from='.today()->toDateString().'&to='.today()->toDateString())
            ->assertOk()
            ->assertViewHas('total', 12500.0)
            ->assertSee('Laboratuvar');
    }

    public function test_finance_report_totals_and_comparison(): void
    {
        $thisMonth = today()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonth();

        Treatment::factory()->create(['price' => 8000, 'status' => TreatmentStatus::Completed, 'treatment_date' => $thisMonth]);
        Treatment::factory()->create(['price' => 500, 'status' => TreatmentStatus::Planned, 'treatment_date' => $thisMonth]);
        Payment::factory()->create(['amount' => 6000, 'paid_at' => $thisMonth]);
        Expense::factory()->create(['amount' => 4000, 'spent_at' => $thisMonth]);
        Payment::factory()->create(['amount' => 3000, 'paid_at' => $lastMonth]);
        Expense::factory()->create(['amount' => 1000, 'spent_at' => $lastMonth]);

        $report = app(FinanceReport::class);

        $this->assertSame(
            ['revenue' => 8000.0, 'collected' => 6000.0, 'expenses' => 4000.0, 'net' => 2000.0],
            $report->totals($thisMonth, $thisMonth->copy()->endOfMonth()),
        );

        $monthly = collect($report->monthly($thisMonth, 2))->keyBy('key');
        $this->assertSame(2000.0, $monthly[$lastMonth->format('Y-m')]['net']);
        $this->assertSame(2000.0, $monthly[$thisMonth->format('Y-m')]['net']);
        $this->assertCount(2, $monthly);

        $this->assertSame(100.0, FinanceReport::change(6000, 3000));
        $this->assertNull(FinanceReport::change(100, 0));
    }

    public function test_comparison_uses_calendar_months(): void
    {
        $report = app(FinanceReport::class);
        $d = fn (string $date) => Carbon::parse($date);
        $period = fn (string $from, string $to) => array_map(fn ($c) => $c->toDateString(), $report->previousPeriod($d($from), $d($to)));

        // Tam ay → önceki tam ay (ayın 1'indeki kira/maaş da dahil)
        $this->assertSame(['2026-08-01', '2026-08-31'], $period('2026-09-01', '2026-09-30'));
        $this->assertSame('Ağustos 2026', $report->previousPeriodLabel($d('2026-09-01'), $d('2026-09-30')));
        // Ay başından bugüne → önceki ayın aynı günleri
        $this->assertSame(['2026-09-01', '2026-09-05'], $period('2026-10-01', '2026-10-05'));
        // Mart sonu taşmaz
        $this->assertSame(['2026-02-01', '2026-02-28'], $period('2026-03-01', '2026-03-31'));
        // Çok aylık tam dönem → önceki aynı uzunlukta dönem
        $this->assertSame(['2026-04-01', '2026-06-30'], $period('2026-07-01', '2026-09-30'));
        // Serbest aralık → hemen önceki aynı gün sayısı
        $this->assertSame(['2026-09-05', '2026-09-14'], $period('2026-09-15', '2026-09-24'));
    }

    public function test_reports_render_income_expense_charts(): void
    {
        Payment::factory()->create(['amount' => 50000, 'paid_at' => today()]);
        Expense::factory()->create(['amount' => 70000, 'spent_at' => today(), 'category' => 'salary']);

        $this->actingAs($this->admin())->get('/reports')
            ->assertOk()
            ->assertSee('Aylık gelen ve giden para')
            ->assertSee('Aylık net kâr / zarar')
            ->assertSee('Giderler nereye gitti?')
            ->assertSee('Personel maaşı')
            ->assertSee('-20.000,00 ₺')
            ->assertSee('Aylık özet tablosu');
    }

    public function test_dashboard_finance_block_is_admin_only(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')->assertSee('Son 6 ay gelen ve giden');
        $this->actingAs($this->secretary())->get('/dashboard')->assertDontSee('Son 6 ay gelen ve giden')->assertSee('Bugünkü gelir');
    }

    public function test_chart_scale_and_compact_money(): void
    {
        $scale = ChartScale::make(0, 137000);
        $this->assertSame(0.0, $scale['min']);
        $this->assertGreaterThanOrEqual(137000, $scale['max']);
        $this->assertSame([0.0, 50000.0, 100000.0, 150000.0], $scale['ticks']);

        $negative = ChartScale::make(-30000, 40000);
        $this->assertLessThanOrEqual(-30000, $negative['min']);
        $this->assertContains(0.0, $negative['ticks']);

        $this->assertSame('12,5 B ₺', Money::compact(12500));
        $this->assertSame('1,2 Mn ₺', Money::compact(1200000));
        $this->assertSame('950 ₺', Money::compact(950));
        $this->assertSame('50 B ₺', Money::compact(50000));
    }
}
