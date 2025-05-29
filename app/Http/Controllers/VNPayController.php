<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\VNPayService;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\OrderStatusService;
use App\GraphQL\Enums\PaymentStatus;
use App\GraphQL\Enums\PaymentMethod;

class VNPayController extends Controller
{
    protected $vnpayService;
    protected $orderStatusService;

    public function __construct(VNPayService $vnpayService, OrderStatusService $orderStatusService)
    {
        $this->vnpayService = $vnpayService;
        $this->orderStatusService = $orderStatusService;
    }

    public function handleIPN(Request $request)
    {
        try {
            // Log request để debug
            Log::info('VNPay IPN Request:', $request->all());

            // Validate input data
            $inputData = $request->all();
            if (empty($inputData)) {
                Log::error('VNPay IPN: Empty input data');
                return response()->json([
                    'RspCode' => '99',
                    'Message' => 'Empty input data'
                ], 400);
            }

            // Kiểm tra các field bắt buộc
            $requiredFields = ['vnp_ResponseCode', 'vnp_TxnRef', 'vnp_Amount', 'vnp_SecureHash'];
            foreach ($requiredFields as $field) {
                if (!isset($inputData[$field])) {
                    Log::error("VNPay IPN: Missing required field: {$field}");
                    return response()->json([
                        'RspCode' => '99',
                        'Message' => "Missing required field: {$field}"
                    ], 400);
                }
            }

            // Xử lý IPN
            $result = $this->vnpayService->handleIPN($inputData);
            
            // Lưu vào database
            $payment = Payment::where('transaction_id', $result['transaction_id'])->first();
            if ($payment) {
                $payment->update([
                    'payment_status' => $result['success'] ? PaymentStatus::COMPLETED : PaymentStatus::FAILED,
                    'amount' => $result['amount'],
                    'payment_time' => now(),
                ]);
            } else {
                // Nếu chưa có payment, tạo mới (tuỳ nghiệp vụ)
                $payment = Payment::create([
                    'order_id' => $result['order_id'],
                    'payment_method' => 'vnpay',
                    'payment_status' => $result['success'] ? PaymentStatus::COMPLETED : PaymentStatus::FAILED,
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $result['amount'],
                    'payment_time' => now(),
                ]);
            }

            //Update Order status using OrderStatusService
            $this->orderStatusService->updateOrderFromPaymentStatus($payment);

            Log::info('VNPay IPN: Payment updated successfully', [
                'order_id' => $result['order_id'],
                'payment_id' => $payment->id
            ]);
            
            return response()->json([
                'RspCode' => '00', 
                'Message' => 'Confirm success'
            ]);
            
        } catch (\Exception $e) {
            Log::error('VNPay IPN Error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'input_data' => $request->all()
            ]);
            
            return response()->json([
                'RspCode' => '99', 
                'Message' => $e->getMessage()
            ], 400);
        }
    }
}