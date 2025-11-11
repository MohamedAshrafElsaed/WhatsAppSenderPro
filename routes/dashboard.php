<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\ContactTagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WhatsAppSessionController;
use Illuminate\Support\Facades\Route;

// ==================== DASHBOARD ROUTES ====================
// All authenticated routes under /dashboard prefix
Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {

    // Dashboard Home
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    Route::resource('templates', TemplateController::class)->names('templates');

    // ==================== WHATSAPP ====================
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('connection', [WhatsAppSessionController::class, 'index'])->name('connection');
        Route::post('sessions', [WhatsAppSessionController::class, 'store'])->name('sessions.store');
        Route::get('sessions/{sessionId}/qr', [WhatsAppSessionController::class, 'getQR'])->name('sessions.qr');
        Route::get('sessions/{sessionId}/status', [WhatsAppSessionController::class, 'status'])->name('sessions.status');
        Route::post('sessions/{sessionId}/refresh', [WhatsAppSessionController::class, 'refresh'])->name('sessions.refresh');
        Route::delete('sessions/{sessionId}', [WhatsAppSessionController::class, 'destroy'])->name('sessions.destroy');
        Route::get('sessions/{sessionId}/ws-url', [WhatsAppSessionController::class, 'getWebSocketUrl'])->name('sessions.ws-url');
    });

    // ==================== ONBOARDING ====================
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::post('complete-step', [OnboardingController::class, 'completeStep'])->name('complete-step');
        Route::get('status', [OnboardingController::class, 'status'])->name('status');
    });

    // ==================== CONTACTS ====================
    Route::prefix('contacts')->name('contacts.')->group(function () {
        // Contact Imports
        Route::prefix('imports')->name('imports.')->group(function () {
            Route::get('/', [ContactImportController::class, 'index'])->name('index');
            Route::post('upload', [ContactImportController::class, 'upload'])->name('upload');
            Route::post('{import}/process', [ContactImportController::class, 'process'])->name('process');
            Route::delete('{import}', [ContactImportController::class, 'destroy'])->name('destroy');
            Route::get('template', [ContactImportController::class, 'downloadTemplate'])->name('template');
            Route::get('{import}/progress', [ContactImportController::class, 'progress'])
                ->name('dashboard.contacts.imports.progress');
        });

        // Contact CRUD
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('create', [ContactController::class, 'create'])->name('create');
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::get('{contact}', [ContactController::class, 'show'])->name('show');
        Route::get('{contact}/edit', [ContactController::class, 'edit'])->name('edit');
        Route::put('{contact}', [ContactController::class, 'update'])->name('update');
        Route::delete('{contact}', [ContactController::class, 'destroy'])->name('destroy');

        // Bulk Actions
        Route::post('bulk-validate', [ContactController::class, 'bulkValidate'])->name('bulk-validate');
    });

    // ==================== CONTACT TAGS ====================
    Route::resource('contact-tags', ContactTagController::class)
        ->except(['show'])
        ->names('contact-tags');

    // ==================== CAMPAIGNS ====================
    // TODO: Add campaign routes when ready
    // Route::resource('campaigns', CampaignController::class);

    // ==================== TEMPLATES ====================
    // TODO: Add template routes when ready
    // Route::resource('templates', TemplateController::class);
});
