<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Services\AuthService;
use App\Services\ZalopayService;
use App\GraphQL\Traits\GraphQLResponse;
use App\Models\Payment;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Log;

final readonly class PaymentResolver
{
    /** @param  array{}  $args */
    protected ZalopayService $zalopayService;
    public function __construct(ZalopayService $zalopayService)
    {
        $this->zalopayService = $zalopayService;
    }
    public function createPaymentZalopay($_, array $args)
    {
        $user = AuthService::Auth(); // pre-handled by middleware
        if(!isset($args['order_id'])) {
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'payment_url' => null,
            ];
        }
        $order=Order::with(['items.product'],'user')->where('id',$args['order_id'])->where('user_id',$user->id)->first();
        if(!$order){
            return [
                'code' => 404,
                'message' => 'Order not found',
                'payment_url' => null,
            ];
        }
        $callbackUrl = route('payment.callback');
        $returnUrl = route('payment.return');

        $result = $this->zalopayService->createPaymentOrder($order, $callbackUrl, $returnUrl);
        Log::info($result);
        //this step, obtain the redirect url to zalopay payment gateway
        if ($result['return_code'] !== 1) {
            
            return [
                'code' => 400,
                'message' => $result['return_message'],
                'payment_url' => null,
                'transaction_id' => null,
            ];
        }
        $payment=Payment::create([
            'order_id' => $args['order_id'],
            'amount' => $order->total_price,
            'payment_method' => 'zalopay',
            'payment_status' => 'pending',
            'transaction_id' => $result['app_trans_id']?? $this->generateTransactionId(),
        ]);
        return [
            'code' => 200,
            'message' => 'Payment created successfully',
            'payment_url' => $result['order_url']?? null,
            'transaction_id' => $payment->transaction_id,
        ];
    }
    public function createPaymentCOD($_, array $args)
    {
        $user = AuthService::Auth(); // pre-handled by middleware
        if(!isset($args['order_id'])) {
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'transaction_id' => null,
            ];
        }
        $order=Order::where('id',$args['order_id'])->where('user_id',$user->id)->first();
        if(!$order){
            return [
                'code' => 404,
                'message' => 'Order not found',
                'transaction_id' => null,
            ];

        }
        $payment=Payment::create([
            'order_id' => $args['order_id'],
            'amount' => $order->total_price,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'transaction_id' => $this->generateTransactionId(),
        ]);
        return [
            'code' => 200,
            'message' => 'Payment created successfully',
            'transaction_id' => $payment->transaction_id,
        ];
    }
    private function generateTransactionId()
    {
        return 'ZP' . time() . rand(1000, 9999);
    }
}
