<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;


Route::get('/', [LandingController::class, 'index'])->name('home');

Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/contact', [StaticPageController::class, 'contact'])->name('contact');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ar'])) {
        session(['locale' => $locale]);

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }

    return redirect()->back();
})->name('lang.switch');
