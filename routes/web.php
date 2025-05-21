<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GHNWebhookController;
use App\Http\Controllers\ZalopayController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('payment/return', [ZalopayController::class, 'return'])->name('payment.return');
