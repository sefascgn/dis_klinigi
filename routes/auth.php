<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Herkese açık kayıt yok: kullanıcı hesaplarını yalnızca yönetici oluşturur.
Route::middleware('guest')->group(function () {
    Route::get('giris', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('giris', [AuthenticatedSessionController::class, 'store']);

    Route::get('sifremi-unuttum', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('sifremi-unuttum', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('sifre-sifirla/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('sifre-sifirla', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('eposta-dogrula', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('eposta-dogrula/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('eposta-dogrula/tekrar-gonder', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('sifre-onayla', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('sifre-onayla', [ConfirmablePasswordController::class, 'store']);

    Route::put('sifre', [PasswordController::class, 'update'])->name('password.update');

    Route::post('cikis', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
