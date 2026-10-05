<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Doctor;
use App\Models\Expense;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\TreatmentType;
use App\Services\FinanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request, FinanceReport $finance): View
    {
        Gate::authorize('viewReports');
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = ($request->date('from') ?? today()->startOfMonth())->startOfDay();
        $to = ($request->date('to') ?? today())->endOfDay();
        $dateRange = [$from->toDateString(), $to->toDateString()];
        $completedInRange = fn ($q) => $q->where('status', TreatmentStatus::Completed)->whereBetween('treatment_date', $dateRange);

        // Aylık grafikler, seçilen dönemin bittiği ay dahil son 12 ayı gösterir.
        $monthly = collect($finance->monthly($to, 12))->map(fn ($row) => $row + [
            'tooltip' => $row['month']->translatedFormat('F Y'),
        ]);

        $appointmentCounts = Appointment::whereBetween('starts_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $appointmentTotal = (int) $appointmentCounts->sum();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'totals' => $finance->totalsWithComparison($from, $to),
            'compareLabel' => $finance->previousPeriodLabel($from, $to),
            'monthly' => $monthly,
            'operations' => [
                'receivable' => (float) Treatment::where('status', '!=', TreatmentStatus::Cancelled)->sum('price') - (float) Payment::sum('amount'),
                'debtors' => DB::query()->fromSub(Patient::query()->withBalance()->debtors()->reorder(), 'debtors')->count(),
                'recall' => Patient::query()->dueForRecall(ClinicSetting::current()->recall_months)->reorder()->count(),
                'new_patients' => Patient::whereBetween('created_at', [$from, $to])->count(),
                'appointments' => $appointmentTotal,
                'cancel_rate' => $appointmentTotal ? round(($appointmentCounts[AppointmentStatus::Cancelled->value] ?? 0) / $appointmentTotal * 100) : 0,
            ],
            'expensesByCategory' => Expense::whereBetween('spent_at', $dateRange)
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')->groupBy('category')->get()
                ->map(fn ($r) => ['label' => $r->category->label(), 'value' => (float) $r->total, 'hint' => $r->count.' kayıt']),
            'revenueByType' => TreatmentType::query()
                ->withCount(['treatments as treatment_count' => $completedInRange])
                ->withSum(['treatments as treatment_total' => $completedInRange], 'price')
                ->get()->filter(fn ($t) => $t->treatment_count > 0)
                ->map(fn ($t) => ['label' => $t->name, 'value' => (float) $t->treatment_total, 'hint' => $t->treatment_count.' işlem']),
            'revenueByDoctor' => Doctor::withTrashed()
                ->withCount(['treatments as treatment_count' => $completedInRange])
                ->withSum(['treatments as treatment_total' => $completedInRange], 'price')
                ->get()->filter(fn ($d) => $d->treatment_count > 0)
                ->map(fn ($d) => ['label' => $d->full_name, 'value' => (float) $d->treatment_total, 'hint' => $d->treatment_count.' işlem']),
            'collectedByMethod' => Payment::whereBetween('paid_at', $dateRange)
                ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')->groupBy('method')->get()
                ->map(fn ($r) => ['label' => $r->method->label(), 'value' => (float) $r->total, 'hint' => $r->count.' ödeme']),
        ]);
    }
}
