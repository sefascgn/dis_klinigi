<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Models\ClinicSetting;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Patient::class);

        // Hazır listeler: borçlular (ödeme yetkisi gerekir) ve kontrol zamanı gelenler.
        $filter = $request->query('filter');
        if ($filter === 'debtors' && $request->user()->cannot('viewAny', Payment::class)) {
            abort(403);
        }

        $patients = Patient::query()
            ->visibleTo($request->user())
            ->search($request->string('q')->trim()->value())
            ->withBalance()
            ->when($filter === 'debtors', fn ($q) => $q->debtors())
            ->when($filter === 'recall', fn ($q) => $q->dueForRecall(ClinicSetting::current()->recall_months))
            ->when(! in_array($filter, ['debtors', 'recall'], true), fn ($q) => $q->latest())
            ->paginate(15)
            ->withQueryString();

        return view('patients.index', compact('patients', 'filter'));
    }

    public function create(): View
    {
        Gate::authorize('create', Patient::class);

        return view('patients.create', ['patient' => new Patient]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        $patient = new Patient($request->validated());
        $patient->created_by = $request->user()->id;
        $patient->save();

        return redirect()->route('patients.show', $patient)->with('success', 'Hasta kaydı oluşturuldu.');
    }

    public function show(Patient $patient): View
    {
        Gate::authorize('view', $patient);

        $patient->load([
            'appointments' => fn ($q) => $q->with('doctor')->latest('starts_at'),
            'treatments' => fn ($q) => $q->with(['doctor', 'treatmentType'])->latest('treatment_date')->latest('id'),
            'payments' => fn ($q) => $q->with('receiver')->latest('paid_at')->latest('id'),
            'documents' => fn ($q) => $q->with('uploader')->latest(),
        ]);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        Gate::authorize('update', $patient);

        return view('patients.edit', compact('patient'));
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return redirect()->route('patients.show', $patient)->with('success', 'Hasta bilgileri güncellendi.');
    }

    /**
     * Hasta arşivlenir (soft delete); randevu, tedavi ve ödeme geçmişi korunur.
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        Gate::authorize('delete', $patient);

        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Hasta kaydı silindi.');
    }
}
