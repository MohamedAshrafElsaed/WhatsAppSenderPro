<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\WhatsAppSessionController;
use Illuminate\Support\Facades\Route;


Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {

    // WhatsApp Connection Routes
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/connection', [WhatsAppSessionController::class, 'index'])->name('connection');
        Route::post('/sessions', [WhatsAppSessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{sessionId}/qr', [WhatsAppSessionController::class, 'getQR'])->name('sessions.qr');
        Route::get('/sessions/{sessionId}/status', [WhatsAppSessionController::class, 'status'])->name('sessions.status');
        Route::post('/sessions/{sessionId}/refresh', [WhatsAppSessionController::class, 'refresh'])->name('sessions.refresh');
        Route::delete('/sessions/{sessionId}', [WhatsAppSessionController::class, 'destroy'])->name('sessions.destroy');
        Route::get('/sessions/{sessionId}/ws-url', [WhatsAppSessionController::class, 'getWebSocketUrl'])->name('sessions.ws-url');
    });

    // Onboarding Routes
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::post('/complete-step', [OnboardingController::class, 'completeStep'])->name('complete-step');
        Route::get('/status', [OnboardingController::class, 'status'])->name('status');
    });
});
