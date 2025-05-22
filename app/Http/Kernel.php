<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GHNWebhookController;
use App\Http\Controllers\ZalopayController;
use App\Http\Controllers\Auth\EmailVerificationController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('webhooks/ghn', [GHNWebhookController::class, 'handleWebhook']);

// Payment webhook routes with extended timeout
Route::post('webhooks/zalopay/callback', [ZalopayController::class, 'callback'])
    ->middleware('web')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]); // Explicitly exclude CSRF for this route

Route::get('payment/return', [ZalopayController::class, 'return'])->name('payment.return');

// Note: Email verification is now handled entirely through the frontend + GraphQL
// The /api/email/verify/{id}/{hash} route is no longer needed