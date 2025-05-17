<?php
namespace App\Http\Controllers;

class PaymentController extends Controller
{
    protected $zalopayService;

    public function __construct(ZalopayService $zalopayService)
    {
        $this->zalopayService = $zalopayService;
    }

    public function createPaymentOrder($orderId)
    {
        $result = $this->zalopayService->createPaymentOrder($orderId);   
        if ($result['code'] === 200 && isset($result['payment_url'])) {
            return redirect($result['payment_url']);

        }
        return response()->json($result);
    }
    public function callback(Request $request)
    {
        $is_valid=$this->zalopayService->verifyCallback($request->all());
        if($is_valid){
            return response()->json(['return_code' => 1, 'return_message' => 'success']);

        }
        return response()->json(['return_code' => 0, 'return_message' => 'failed']);

    }
    public function paymentStatus($transactionId)
    {
        $result = $this->zalopayService->getTransactionStatus($transactionId);
        return response()->json($result);
    }
    public function redirectAfterPayment(Request $request)
    {
        $orderId = $request->input('order_id');
        $status = $request->input('status');
        
        if ($status == 1) {
            return redirect()->route('payment.success', ['order_id' => $orderId]);
        } else {
            return redirect()->route('payment.failed', ['order_id' => $orderId]);
        }
    }
}