<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Expense;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\FinanceReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FinanceReport $financeReport): View
    {
        $user = $request->user();
        $today = today();

        // Doktor kendi randevu istatistiklerini, diğer roller kliniğin tamamını görür.
        $appointments = fn () => Appointment::query()->visibleTo($user);

        $stats = [
            'today' => $appointments()->onDate($today)->whereNot('status', AppointmentStatus::Cancelled)->count(),
            'pending' => $appointments()->where('status', AppointmentStatus::Pending)->where('starts_at', '>=', $today)->count(),
            'completed_today' => $appointments()->onDate($today)->where('status', AppointmentStatus::Completed)->count(),
            'patients' => Patient::count(),
        ];

        $revenue = null;
        if ($user->can('viewAny', Payment::class)) {
            $revenue = [
                'daily' => (float) Payment::whereDate('paid_at', $today)->sum('amount'),
                'monthly' => (float) Payment::whereBetween('paid_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])->sum('amount'),
            ];
        }

        // Yönetici için bu ayın gelir–gider özeti ve son 6 ay grafiği.
        $finance = null;
        if ($user->can('viewAny', Expense::class)) {
            $finance = [
                'totals' => $financeReport->totalsWithComparison($today->copy()->startOfMonth(), $today),
                'compareLabel' => $financeReport->previousPeriodLabel($today->copy()->startOfMonth(), $today),
                'monthly' => collect($financeReport->monthly($today, 6))
                    ->map(fn ($row) => $row + ['tooltip' => $row['month']->translatedFormat('F Y')])
                    ->all(),
            ];
        }

        $todaySchedule = $appointments()
            ->onDate($today)
            ->with(['patient', 'doctor'])
            ->orderBy('starts_at')
            ->get();

        $upcoming = $appointments()
            ->where('starts_at', '>', $today->copy()->endOfDay())
            ->upcoming()
            ->with(['patient', 'doctor'])
            ->limit(8)
            ->get();

        return view('dashboard', compact('stats', 'revenue', 'finance', 'todaySchedule', 'upcoming'));
    }
}
