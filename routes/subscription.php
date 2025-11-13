<?php

use App\Http\Controllers\Settings\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])
        ->name('subscription.upgrade');

    Route::get('/subscription', [SubscriptionController::class, 'index'])
        ->name('subscription.index');

    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
        ->name('subscription.cancel');

});
