<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GHNWebhookController;
Route::get('/', function () {
    return view('welcome');
});
Route::post('webhooks/ghn', [GHNWebhookController::class, 'handleWebhook']);

Route::get('/payment/zalopay/{orderId}', [PaymentController::class, 'createZalopayPayment'])->name('payment.zalopay');
Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payment/status/{transactionId}', [PaymentController::class, 'paymentStatus'])->name('payment.status');
Route::get('/payment/redirect', [PaymentController::class, 'redirectAfterPayment'])->name('payment.redirect');
Route::get('/payment/success/{order_id}', [OrderController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/failed/{order_id}', [OrderController::class, 'paymentFailed'])->name('payment.failed');