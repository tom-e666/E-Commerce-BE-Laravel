<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\GHNWebhookController;
use App\Http\Controllers\ZalopayController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('payment/return', [ZalopayController::class, 'return'])->name('payment.return');

Route::post('/vnpay/ipn', function (Request $request) {
    $data = $request->all();
    
    $mutation = '
        mutation VNPayIPN($input: VNPayIPNInput!) {
            vnpayIPN(input: $input) {
                success
                message
                order_id
                transaction_id
            }
        }
    ';

    $variables = [
        'input' => [
            'vnp_ResponseCode' => $data['vnp_ResponseCode'],
            'vnp_TxnRef' => $data['vnp_TxnRef'],
            'vnp_Amount' => $data['vnp_Amount'],
            'vnp_TransactionNo' => $data['vnp_TransactionNo'] ?? null,
            'vnp_BankCode' => $data['vnp_BankCode'] ?? null,
            'vnp_PayDate' => $data['vnp_PayDate'] ?? null,
            'vnp_SecureHash' => $data['vnp_SecureHash'],
        ],
    ];

    $response = app(\Nuwave\Lighthouse\GraphQL::class)
        ->executeQuery($mutation, null, $variables)
        ->toArray();

    return response()->json($response['data']['vnpayIPN'] ?? ['success' => false]);
});