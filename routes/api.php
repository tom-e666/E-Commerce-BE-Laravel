<?php
Route::post('payment/callback', [App\Http\Controllers\ZalopayController::class, 'callback'])
->name('payment.callback');