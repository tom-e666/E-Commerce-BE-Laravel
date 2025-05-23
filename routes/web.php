<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\GHNWebhookController;
use App\Http\Controllers\ZalopayController;
use App\Http\Controllers\VNPayController;
use App\Services\VNPayService;
use App\Models\Payment;

Route::get('/', function () {
    return view('welcome');
});

Route::get('payment/return', [ZalopayController::class, 'return'])->name('payment.return');

Route::get('/vnpay/ipn', [VNPayController::class, 'handleIPN']);

// Route::get('/vnpay/ipn', function (Request $request) {
//     // 1. Thiết lập luôn trả về JSON
//     $request->headers->set('Accept', 'application/json');
    
//     // 2. Chuẩn bị dữ liệu response mặc định
//     $returnData = [
//         'RspCode' => '99',
//         'Message' => 'Unknow error'
//     ];

//     try {
//         // 3. Lấy và kiểm tra dữ liệu
//         $inputData = $request->query();
        
//         if (!isset($inputData['vnp_SecureHash'])) {
//             throw new \Exception('Invalid parameter');
//         }

//         // 4. Validate SecureHash
//         $vnpayService = app(VNPayService::class);
//         if (!$vnpayService->validateReturn($inputData)) {
//             $returnData = [
//                 'RspCode' => '97',
//                 'Message' => 'Invalid signature'
//             ];
//             return response()->json($returnData);
//         }

//         // 5. Kiểm tra đơn hàng
//         $transaction_id = $inputData['vnp_TxnRef'];
//         $amount = $inputData['vnp_Amount'] / 100;
        
//         $order = Payment::where('transaction_id', $transaction_id)->first();
        
//         if (!$order) {
//             $returnData = [
//                 'RspCode' => '01',
//                 'Message' => 'Order not found'
//             ];
//             return response()->json($returnData);
//         }

//         // 6. Kiểm tra số tiền
//         if ($order->amount != $amount) {
//             $returnData = [
//                 'RspCode' => '04',
//                 'Message' => 'Invalid amount'
//             ];
//             return response()->json($returnData);
//         }

//         // 7. Kiểm tra trạng thái đơn hàng
//         if ($order->status !== 'pending') {
//             $returnData = [
//                 'RspCode' => '02',
//                 'Message' => 'Order already confirmed'
//             ];
//             return response()->json($returnData);
//         }

//         // 8. Cập nhật trạng thái
//         if ($inputData['vnp_ResponseCode'] == '00') {
//             $order->update([
//                 'status' => 'completed',
//                 'transaction_id' => $inputData['vnp_TransactionNo'],
//                 'bank_code' => $inputData['vnp_BankCode'] ?? null
//             ]);
            
//             $returnData = [
//                 'RspCode' => '00',
//                 'Message' => 'Confirm Success'
//             ];
//         } else {
//             $order->update(['status' => 'failed']);
//             $returnData = [
//                 'RspCode' => '00', // Vẫn trả 00 vì đã xử lý xong
//                 'Message' => 'Payment failed'
//             ];
//         }

//     } catch (\Exception $e) {
//         Log::error('VNPAY IPN ERROR: '.$e->getMessage());
//         $returnData = [
//             'RspCode' => '99',
//             'Message' => $e->getMessage()
//         ];
//     }

//     // 9. Trả về JSON (QUAN TRỌNG)
//     return response()->json($returnData);
// })->withoutMiddleware([/* Tắt tất cả middleware không cần thiết */]);