<?php

namespace App\Http\Controllers;

use App\Services\ZalopayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZalopayController extends Controller
{
    protected $zalopayService;

    public function __construct(ZalopayService $zalopayService)
    {
        $this->zalopayService = $zalopayService;
    }
    public function callback(Request $request)
    {
        try {
            Log::info('ZaloPay Callback received', ['data' => $request->all()]);
            
            $isValid = $this->zalopayService->verifyCallback($request->all());
            
            if ($isValid) {
                return response()->json(['return_code' => 1, 'return_message' => 'success']);
            }
            
            Log::warning('ZaloPay callback validation failed', ['data' => $request->all()]);
            return response()->json(['return_code' => 0, 'return_message' => 'failed']);
        } catch (\Exception $e) {
            Log::error('ZaloPay callback error', ['error' => $e->getMessage()]);
            return response()->json(['return_code' => 0, 'return_message' => 'internal error']);
        }
    }
    public function return(Request $request)
    {
        $orderId = $request->input('order_id');
        $status = $request->input('status'); 
        Log::info('User returned from ZaloPay', ['order_id' => $orderId, 'status' => $status]);
        
        $frontendUrl = env('FRONTEND_URL');
        return redirect()->away($frontendUrl . "/checkout/result?order_id={$orderId}&status={$status}");
    }
    public function paymentStatus($transactionId)
    {
        $result = $this->zalopayService->getTransactionStatus($transactionId);
        return response()->json($result);
    }
}