<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClinicSettingRequest;
use App\Models\ClinicSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClinicSettingController extends Controller
{
    public function edit(): View
    {
        $setting = ClinicSetting::current();
        Gate::authorize('update', $setting);

        return view('clinic-settings.edit', compact('setting'));
    }

    public function update(ClinicSettingRequest $request): RedirectResponse
    {
        ClinicSetting::current()->update($request->validated());

        return back()->with('success', 'Klinik bilgileri güncellendi.');
    }
}
