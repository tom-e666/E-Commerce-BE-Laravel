<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GHNWebhookController;
use App\Http\Controllers\ZalopayController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('webhooks/ghn', [GHNWebhookController::class, 'handleWebhook']);

Route::get('payment/return', [ZalopayController::class, 'return'])->name('payment.return');

// Note: Email verification is now handled entirely through the frontend + GraphQL
// The /api/email/verify/{id}/{hash} route is no longer needed
