<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SubscriptionController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->prefix('dashboard/settings')->name('dashboard.settings.')->group(function () {

    // Redirect /dashboard/settings to /dashboard/settings/profile
    Route::redirect('/', '/dashboard/settings/profile');

    // Profile Settings
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Password Settings
    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Subscription Settings
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/select-package', [SubscriptionController::class, 'selectPackage'])->name('selectPackage');
        Route::get('/payment/{packageSlug}', [SubscriptionController::class, 'payment'])->name('payment');
        Route::post('/process-payment', [SubscriptionController::class, 'processPayment'])->name('processPayment');
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    });

    // Appearance Settings
    Route::get('appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    // Two-Factor Authentication
    Route::get('two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
