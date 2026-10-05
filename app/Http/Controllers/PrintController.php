<?php

namespace App\Http\Controllers;

use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tarayıcıdan yazdırılan / PDF olarak kaydedilen belgeler.
 */
class PrintController extends Controller
{
    /**
     * Tedavi planı ve fiyat teklifi: planlanan ve devam eden işlemler.
     */
    public function plan(Patient $patient): View
    {
        Gate::authorize('view', $patient);

        $treatments = $patient->treatments()
            ->whereIn('status', [TreatmentStatus::Planned, TreatmentStatus::InProgress])
            ->with(['treatmentType', 'doctor'])
            ->orderBy('treatment_date')
            ->get();

        return view('print.plan', compact('patient', 'treatments'));
    }

    /**
     * Hesap ekstresi: tüm tedavi borçları ve ödemeler, tarih sırasıyla yürüyen bakiye.
     */
    public function statement(Patient $patient): View
    {
        Gate::authorize('viewAny', Payment::class);

        $rows = collect()
            ->merge($patient->billableTreatments()->with('treatmentType')->get()->map(fn ($t) => [
                'date' => $t->treatment_date,
                'description' => $t->treatmentType->name.($t->tooth_number ? ' (Diş '.$t->tooth_number.')' : ''),
                'debit' => (float) $t->price,
                'credit' => 0.0,
            ]))
            ->merge($patient->payments()->get()->map(fn ($p) => [
                'date' => $p->paid_at,
                'description' => 'Ödeme – '.$p->method->label().($p->notes ? ' ('.$p->notes.')' : ''),
                'debit' => 0.0,
                'credit' => (float) $p->amount,
            ]))
            ->sortBy('date')
            ->values();

        return view('print.statement', compact('patient', 'rows'));
    }

    public function receipt(Payment $payment): View
    {
        Gate::authorize('viewAny', Payment::class);

        $payment->load(['patient', 'receiver']);
        $patient = Patient::withTrashed()->withBalance()->findOrFail($payment->patient_id);

        return view('print.receipt', compact('payment', 'patient'));
    }
}
