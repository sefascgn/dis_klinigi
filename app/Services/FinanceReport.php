<?php

namespace App\Services;

use App\Enums\TreatmentStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Treatment;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Klinik finans özetleri. Tanımlar:
 *  - Ciro:      tamamlanan tedavilerin tutarı (yapılan iş, tedavi tarihine göre)
 *  - Tahsilat:  hastalardan alınan ödemeler (kasaya giren para)
 *  - Gider:     kaydedilen giderler (kasadan çıkan para)
 *  - Net:       tahsilat − gider (nakit bazında kâr / zarar)
 */
class FinanceReport
{
    /**
     * @return array{revenue: float, collected: float, expenses: float, net: float}
     */
    public function totals(CarbonInterface $from, CarbonInterface $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];

        $revenue = (float) Treatment::where('status', TreatmentStatus::Completed)->whereBetween('treatment_date', $range)->sum('price');
        $collected = (float) Payment::whereBetween('paid_at', $range)->sum('amount');
        $expenses = (float) Expense::whereBetween('spent_at', $range)->sum('amount');

        return [
            'revenue' => $revenue,
            'collected' => $collected,
            'expenses' => $expenses,
            'net' => round($collected - $expenses, 2),
        ];
    }

    /**
     * Aynı uzunluktaki bir önceki döneme göre karşılaştırmalı özet.
     *
     * @return array<string, array{value: float, previous: float, change: ?float}>
     */
    public function totalsWithComparison(CarbonInterface $from, CarbonInterface $to): array
    {
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        $current = $this->totals($from, $to);
        $previous = $this->totals($prevFrom, $prevTo);

        return collect($current)->map(fn ($value, $key) => [
            'value' => $value,
            'previous' => $previous[$key],
            'change' => self::change($value, $previous[$key]),
        ])->all();
    }

    /**
     * Karşılaştırma dönemi. Kira, maaş gibi giderler ayın belirli günlerinde ödendiği için
     * ay başından başlayan seçimler takvim ayıyla karşılaştırılır:
     *  - Tam ay(lar) (ör. Eylül)       → bir önceki aynı uzunlukta takvim dönemi (Ağustos)
     *  - Ay başından bugüne (1–5 Ekim)  → önceki ayın aynı günleri (1–5 Eylül)
     *  - Diğer aralıklar               → hemen önceki aynı gün sayısı
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function previousPeriod(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = Carbon::instance($from)->startOfDay();
        $to = Carbon::instance($to)->startOfDay();

        if ($from->day === 1) {
            if ($to->isSameDay($to->copy()->endOfMonth())) {
                $months = ($to->year - $from->year) * 12 + $to->month - $from->month + 1;

                return [$from->copy()->subMonthsNoOverflow($months), $from->copy()->subDay()];
            }

            if ($from->isSameMonth($to)) {
                return [$from->copy()->subMonthNoOverflow(), $to->copy()->subMonthNoOverflow()];
            }
        }

        $days = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay();

        return [$prevTo->copy()->subDays($days - 1), $prevTo];
    }

    /**
     * Karşılaştırma döneminin kısa adı: "Ağustos 2026" veya "02.08 – 31.08.2026".
     */
    public function previousPeriodLabel(CarbonInterface $from, CarbonInterface $to): string
    {
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        return $prevFrom->day === 1 && $prevTo->isSameDay($prevTo->copy()->endOfMonth()) && $prevFrom->isSameMonth($prevTo)
            ? $prevFrom->translatedFormat('F Y')
            : $prevFrom->format('d.m').' – '.$prevTo->format('d.m.Y');
    }

    /**
     * $end ayı dahil son $months ayın aylık kırılımı (boş aylar 0).
     *
     * @return list<array{key: string, label: string, month: Carbon, revenue: float, collected: float, expenses: float, net: float}>
     */
    public function monthly(CarbonInterface $end, int $months = 12): array
    {
        $start = Carbon::instance($end)->startOfMonth()->subMonths($months - 1);
        $endDate = Carbon::instance($end)->endOfMonth()->toDateString();

        $sum = fn ($query, string $dateColumn, string $valueColumn) => $query
            ->whereBetween($dateColumn, [$start->toDateString(), $endDate])
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as ym, SUM({$valueColumn}) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $revenue = $sum(Treatment::where('status', TreatmentStatus::Completed), 'treatment_date', 'price');
        $collected = $sum(Payment::query(), 'paid_at', 'amount');
        $expenses = $sum(Expense::query(), 'spent_at', 'amount');

        $rows = [];
        for ($month = $start->copy(); count($rows) < $months; $month->addMonth()) {
            $key = $month->format('Y-m');
            $c = (float) ($collected[$key] ?? 0);
            $e = (float) ($expenses[$key] ?? 0);
            $rows[] = [
                'key' => $key,
                'label' => $month->translatedFormat('M'),
                'month' => $month->copy(),
                'revenue' => (float) ($revenue[$key] ?? 0),
                'collected' => $c,
                'expenses' => $e,
                'net' => round($c - $e, 2),
            ];
        }

        return $rows;
    }

    /**
     * Yüzde değişim; önceki dönem 0 ise karşılaştırma anlamsızdır (null).
     */
    public static function change(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.01) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }
}
