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

Route::redirect('/', '/panel');

// Adresler Türkçe, route isimleri İngilizce: view ve controller'lar route() ile isim kullanır.
// "create" / "edit" adresleri AppServiceProvider'da "yeni" / "duzenle" olarak tanımlı.
// Yetki kontrolleri her controller içinde ilgili Policy üzerinden yapılır.
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('panel', DashboardController::class)->name('dashboard');

    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('hastalar/ara', PatientSearchController::class)->name('patients.search');
    Route::resource('hastalar', PatientController::class)->names('patients')->parameters(['hastalar' => 'patient']);

    Route::post('hastalar/{patient}/belgeler', [PatientDocumentController::class, 'store'])->name('patients.documents.store');
    Route::get('belgeler/{document}', [PatientDocumentController::class, 'show'])->name('documents.show');
    Route::delete('belgeler/{document}', [PatientDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('hastalar/{patient}/yazdir/tedavi-plani', [PrintController::class, 'plan'])->name('print.plan');
    Route::get('hastalar/{patient}/yazdir/hesap-ekstresi', [PrintController::class, 'statement'])->name('print.statement');
    Route::get('odemeler/{payment}/makbuz', [PrintController::class, 'receipt'])->name('print.receipt');

    Route::get('raporlar', ReportController::class)->name('reports.index');

    Route::resource('doktorlar', DoctorController::class)->names('doctors')->parameters(['doktorlar' => 'doctor']);

    Route::patch('randevular/{appointment}/durum', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::resource('randevular', AppointmentController::class)->except('show')->names('appointments')->parameters(['randevular' => 'appointment']);

    Route::resource('islem-turleri', TreatmentTypeController::class)->except('show')->names('treatment-types')->parameters(['islem-turleri' => 'treatment_type']);
    Route::resource('tedaviler', TreatmentController::class)->except('show')->names('treatments')->parameters(['tedaviler' => 'treatment']);
    Route::resource('odemeler', PaymentController::class)->except('show')->names('payments')->parameters(['odemeler' => 'payment']);
    Route::resource('giderler', ExpenseController::class)->except('show')->names('expenses')->parameters(['giderler' => 'expense']);
    Route::resource('kullanicilar', UserController::class)->except('show')->names('users')->parameters(['kullanicilar' => 'user']);

    Route::get('ayarlar/klinik', [ClinicSettingController::class, 'edit'])->name('clinic-settings.edit');
    Route::put('ayarlar/klinik', [ClinicSettingController::class, 'update'])->name('clinic-settings.update');
});

require __DIR__.'/auth.php';
