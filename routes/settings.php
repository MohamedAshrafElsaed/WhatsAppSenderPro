<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==================== SETTINGS ROUTES ====================
// All settings routes under /dashboard/settings prefix
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

    // Appearance Settings
    Route::get('appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    // Two-Factor Authentication
    Route::get('two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

// ==================== LEGACY ROUTES (Keep for backward compatibility) ====================
// These will redirect to new routes for a smooth transition
Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/dashboard/settings/profile');
    Route::redirect('settings/profile', '/dashboard/settings/profile');
    Route::redirect('settings/password', '/dashboard/settings/password');
    Route::redirect('settings/appearance', '/dashboard/settings/appearance');
    Route::redirect('settings/two-factor', '/dashboard/settings/two-factor');
});
