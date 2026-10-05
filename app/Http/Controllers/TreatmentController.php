<?php

namespace App\Http\Controllers;

use App\Enums\TreatmentStatus;
use App\Http\Requests\TreatmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\TreatmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Treatment::class);

        $status = TreatmentStatus::tryFrom((string) $request->query('status'));

        $treatments = Treatment::query()
            ->visibleTo($request->user())
            ->with(['patient', 'doctor', 'treatmentType'])
            ->when($request->integer('doctor_id'), fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($request->integer('treatment_type_id'), fn ($q, $id) => $q->where('treatment_type_id', $id))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->whereHas('patient', fn ($p) => $p->search($term)))
            ->latest('treatment_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('treatments.index', [
            'treatments' => $treatments,
            'doctors' => Doctor::ordered()->get(),
            'treatmentTypes' => TreatmentType::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Treatment::class);

        $appointment = $request->integer('appointment_id')
            ? Appointment::find($request->integer('appointment_id'))
            : null;

        $treatment = new Treatment([
            'patient_id' => $appointment?->patient_id ?? ($request->integer('patient_id') ?: null),
            'doctor_id' => $appointment?->doctor_id ?? $request->user()->doctor?->id,
            'appointment_id' => $appointment?->id,
            'status' => TreatmentStatus::Completed,
            'treatment_date' => $appointment?->starts_at ?? today(),
        ]);

        return view('treatments.create', $this->formData($treatment));
    }

    public function store(TreatmentRequest $request): RedirectResponse
    {
        $treatment = Treatment::create($request->validated());

        return redirect()->route('patients.show', $treatment->patient_id)->with('success', 'Tedavi kaydedildi.');
    }

    public function edit(Treatment $treatment): View
    {
        Gate::authorize('update', $treatment);

        return view('treatments.edit', $this->formData($treatment));
    }

    public function update(TreatmentRequest $request, Treatment $treatment): RedirectResponse
    {
        $treatment->update($request->validated());

        return redirect()->route('patients.show', $treatment->patient_id)->with('success', 'Tedavi güncellendi.');
    }

    public function destroy(Treatment $treatment): RedirectResponse
    {
        Gate::authorize('delete', $treatment);

        $treatment->delete();

        return back()->with('success', 'Tedavi kaydı silindi.');
    }

    private function formData(Treatment $treatment): array
    {
        return [
            'treatment' => $treatment,
            'patient' => $treatment->patient_id ? Patient::withTrashed()->find($treatment->patient_id) : null,
            'doctors' => Doctor::active()->ordered()->get(),
            // Düzenlemede, sonradan pasifleşmiş işlem de listede kalmalı.
            'treatmentTypes' => TreatmentType::query()
                ->where(fn ($q) => $q->active()->when($treatment->treatment_type_id, fn ($q, $id) => $q->orWhere('id', $id)))
                ->orderBy('name')
                ->get(),
        ];
    }
}
