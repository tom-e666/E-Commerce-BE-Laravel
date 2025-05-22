<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

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
        $vnp_OrderType = $paymentData['order_type'];
        $vnp_Amount = $paymentData['amount'] * 100;
        $vnp_Locale = $paymentData['locale'] ?? 'vn';
        $vnp_BankCode = $paymentData['bank_code'] ?? '';
        $vnp_IpAddr = request()->ip();

        // $expireMinutes = $paymentData['expire_minutes'] ?? 15;
        // $vnp_ExpireDate = date('YmdHis', strtotime("+{$expireMinutes} minutes"));
        
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
            // "vnp_ExpireDate" => $vnp_ExpireDate,
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
        $vnp_SecureHash = $data['vnp_SecureHash'];
        unset($data['vnp_SecureHash']);
        
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
        return $secureHash === $vnp_SecureHash;
    }

    public function handleIPN(array $ipnData)
    {
        // 1. Kiểm tra chữ ký
        if (!$this->validateReturn($ipnData)) {
            throw new \Exception('Chữ ký không hợp lệ');
        }

        // 2. Kiểm tra mã phản hồi
        if ($ipnData['vnp_ResponseCode'] !== '00') {
            throw new \Exception('Thanh toán thất bại. Mã lỗi: '.$ipnData['vnp_ResponseCode']);
        }

        // 3. Trả về dữ liệu hợp lệ
        return [
            'success' => true,
            'order_id' => $ipnData['vnp_TxnRef'],
            'amount' => $ipnData['vnp_Amount'] / 100, // Chuyển về đơn vị VND
            'transaction_id' => $ipnData['vnp_TransactionNo'],
            'bank_code' => $ipnData['vnp_BankCode'] ?? null
        ];
    }
}