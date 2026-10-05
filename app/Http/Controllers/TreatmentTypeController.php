<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreatmentTypeRequest;
use App\Models\TreatmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TreatmentTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', TreatmentType::class);

        $treatmentTypes = TreatmentType::query()
            ->withCount('treatments')
            ->orderBy('name')
            ->paginate(20);

        return view('treatment-types.index', compact('treatmentTypes'));
    }

    public function create(): View
    {
        Gate::authorize('create', TreatmentType::class);

        return view('treatment-types.create', ['treatmentType' => new TreatmentType(['is_active' => true])]);
    }

    public function store(TreatmentTypeRequest $request): RedirectResponse
    {
        TreatmentType::create($request->validated());

        return redirect()->route('treatment-types.index')->with('success', 'İşlem tanımı eklendi.');
    }

    public function edit(TreatmentType $treatmentType): View
    {
        Gate::authorize('update', $treatmentType);

        return view('treatment-types.edit', compact('treatmentType'));
    }

    public function update(TreatmentTypeRequest $request, TreatmentType $treatmentType): RedirectResponse
    {
        $treatmentType->update($request->validated());

        return redirect()->route('treatment-types.index')->with('success', 'İşlem tanımı güncellendi.');
    }

    public function destroy(TreatmentType $treatmentType): RedirectResponse
    {
        Gate::authorize('delete', $treatmentType);

        if ($treatmentType->treatments()->exists()) {
            return back()->with('error', 'Bu işlem hasta kayıtlarında kullanıldığı için silinemez. Bunun yerine pasif hale getirebilirsiniz.');
        }

        $treatmentType->delete();

        return back()->with('success', 'İşlem tanımı silindi.');
    }
}
