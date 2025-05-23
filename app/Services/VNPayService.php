<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class VNPayService
{
    public function createPayment(array $paymentData)
    {
        $vnp_Url = config('services.vnpay.url');
        $vnp_Returnurl = config('services.vnpay.return_url');
        $vnp_TmnCode = config('services.vnpay.tmn_code');
        $vnp_HashSecret = config('services.vnpay.hash_secret');
        
        $vnp_TxnRef = $paymentData['order_id'];
        $vnp_OrderInfo = $paymentData['order_info'];
        $vnp_OrderType = $paymentData['order_type'] ?? 'other';
        $vnp_Amount = $paymentData['amount'] * 100;
        $vnp_Locale = $paymentData['locale'] ?? 'vn';
        $vnp_BankCode = $paymentData['bank_code'] ?? '';
        $vnp_IpAddr = request()->ip();
        
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];
        
        if (!empty($vnp_BankCode)) {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        
        return $vnp_Url;
    }
    
    public function validateReturn(array $data)
    {
        $vnp_HashSecret = config('services.vnpay.hash_secret');
        
        if (!isset($data['vnp_SecureHash'])) {
            Log::error('VNPay validation: Missing vnp_SecureHash');
            return false;
        }
        
        $vnp_SecureHash = $data['vnp_SecureHash'];
        unset($data['vnp_SecureHash']);
        unset($data['vnp_SecureHashType']); // Remove if exists
        
        ksort($data);
        $i = 0;
        $hashData = "";
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        
        Log::info('VNPay signature validation', [
            'expected' => $secureHash,
            'received' => $vnp_SecureHash,
            'hash_data' => $hashData
        ]);
        
        return $secureHash === $vnp_SecureHash;
    }

    public function handleIPN(array $ipnData)
    {
        Log::info('VNPay IPN Data:', $ipnData);

        // 1. Kiểm tra chữ ký
        if (!$this->validateReturn($ipnData)) {
            Log::error('VNPay IPN: Invalid signature', $ipnData);
            throw new \Exception('Chữ ký không hợp lệ');
        }

        // 2. Kiểm tra mã phản hồi
        $responseCode = $ipnData['vnp_ResponseCode'];
        $transactionId = $ipnData['vnp_TxnRef']; // transaction_id
        $payment = \App\Models\Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::error('VNPay IPN: Payment not found', ['transaction_id' => $transactionId]);
            throw new \Exception('Payment not found');
        }

        $orderId = $payment->order_id;

        $result = [
            'success' => $responseCode === '00',
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'amount' => $ipnData['vnp_Amount'] / 100,
            'bank_code' => $ipnData['vnp_BankCode'] ?? null,
            'response_code' => $responseCode
        ];

        if ($responseCode !== '00') {
            Log::warning('VNPay IPN: Payment failed', [
                'response_code' => $responseCode,
                'transaction_id' => $transactionId,
                'order_id' => $orderId
            ]);
        }

        return $result;
    }
}