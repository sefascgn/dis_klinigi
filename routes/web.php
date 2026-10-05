<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ClinicSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientDocumentController;
use App\Http\Controllers\PatientSearchController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\TreatmentTypeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Yetki kontrolleri her controller içinde ilgili Policy üzerinden yapılır.
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('patients/search', PatientSearchController::class)->name('patients.search');
    Route::resource('patients', PatientController::class);

    Route::post('patients/{patient}/documents', [PatientDocumentController::class, 'store'])->name('patients.documents.store');
    Route::get('documents/{document}', [PatientDocumentController::class, 'show'])->name('documents.show');
    Route::delete('documents/{document}', [PatientDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('patients/{patient}/print/plan', [PrintController::class, 'plan'])->name('print.plan');
    Route::get('patients/{patient}/print/statement', [PrintController::class, 'statement'])->name('print.statement');
    Route::get('payments/{payment}/receipt', [PrintController::class, 'receipt'])->name('print.receipt');

    Route::get('reports', ReportController::class)->name('reports.index');

    Route::resource('doctors', DoctorController::class);

    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::resource('appointments', AppointmentController::class)->except('show');

    Route::resource('treatment-types', TreatmentTypeController::class)->except('show');
    Route::resource('treatments', TreatmentController::class)->except('show');
    Route::resource('payments', PaymentController::class)->except('show');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');

    Route::get('settings/clinic', [ClinicSettingController::class, 'edit'])->name('clinic-settings.edit');
    Route::put('settings/clinic', [ClinicSettingController::class, 'update'])->name('clinic-settings.update');
});

require __DIR__.'/auth.php';
