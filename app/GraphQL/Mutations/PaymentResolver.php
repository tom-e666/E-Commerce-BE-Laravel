<?php declare(strict_types=1);
use App\Models\Order;
use App\Services\AuthService;
use App\Services\ZalopayService;
use App\GraphQL\Traits\GraphQLResponse;
use App\Models\Payment;
use App\Models\UserCredentail;

namespace App\GraphQL\Mutations;

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
        $order=Order::where('id',$args['order_id'])->where('user_id',$user->id)->first();
        if(!$order){
            return [
                'code' => 404,
                'message' => 'Order not found',
                'payment_url' => null,
            ];
        }
        $result = $this->zalopayService->createPaymentOrder($args['order_id']);
        if ($result['code'] !== 200) {
            return [
                'code' => 400,
                'message' => $result['return_message'],
                'payment_url' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => $result['return_message'],
            'payment_url' => $result['payment_url'],
        ];
    }
    public function createPaymentCOD($_, array $args)
    {
        
    }
}
