<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ZalopayController;

Route::post('payment/callback', [ZalopayController::class, 'callback'])
    ->name('payment.callback');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify');
Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth:api'])
    ->name('verification.resend');

Route::post('/vnpay/ipn', function (Request $request) {
$vnpayService = app(VNPayService::class);

try {
    $result = $vnpayService->handleIPN($request->all());
    
    // Lưu vào database
    Payment::updateOrCreate(
        ['order_id' => $result['order_id']],
        $result
    );
    
    return response()->json(['RspCode' => '00', 'Message' => 'Confirm success']);
    
} catch (\Exception $e) {
    return response()->json(['RspCode' => '99', 'Message' => $e->getMessage()], 400);
}
});