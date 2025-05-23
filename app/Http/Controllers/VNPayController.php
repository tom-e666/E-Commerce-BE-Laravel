<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\VNPayService;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VNPayController extends Controller
{
    protected $vnpayService;

    public function __construct(VNPayService $vnpayService)
    {
        $this->vnpayService = $vnpayService;
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
                    'payment_status' => $result['success'] ? 'completed' : 'failed',
                    'amount' => $result['amount'],
                    'payment_time' => now(),
                ]);
            } else {
                // Nếu chưa có payment, tạo mới (tuỳ nghiệp vụ)
                $payment = Payment::create([
                    'order_id' => $result['order_id'],
                    'payment_method' => 'vnpay',
                    'payment_status' => $result['success'] ? 'confirmed' : 'failed',
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $result['amount'],
                    'payment_time' => now(),
                ]);
            }

            //Update Order status
            $order = $payment->order;
            if ($order) {
                $order->update([
                    'status' => $result['success'] ? 'confirmed' : 'failed'
                ]);
            }

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

    // KHÔNG CẦN handleReturn cho backend API
    // Return URL sẽ trỏ đến frontend app của bạn
    
    // API endpoint để frontend check trạng thái payment
    public function checkPaymentStatus(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required'
                ], 400);
            }
            
            $payment = Payment::where('order_id', $orderId)->first();
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_status' => $payment->payment_status,
                    'amount' => $payment->amount,
                    'transaction_id' => $payment->transaction_id,
                    'payment_time' => $payment->payment_time,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Check Payment Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'System error'
            ], 500);
        }
    }
}