<?php

use App\Http\Controllers\PaymobCallbackController;

require __DIR__ . '/landing.php';
require __DIR__ . '/dashboard.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/subscription.php';


Route::prefix('paymob')->name('paymob.')->group(function () {
    Route::post('/callback', [PaymobCallbackController::class, 'handle'])->name('callback');
    Route::get('/success', [PaymobCallbackController::class, 'success'])->name('success');
    Route::get('/failed', [PaymobCallbackController::class, 'failed'])->name('failed');
});
