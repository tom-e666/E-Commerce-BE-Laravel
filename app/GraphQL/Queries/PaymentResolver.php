<?php 
namespace App\GraphQL\Queries;
use App\GraphQL\Queries\PaymentResolver;
use App\Models\Payment;
final readonly class PaymentResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function getPayment($_, array $args)
    {
        $orderId = $args['order_id'] ?? null;
        if (!$orderId) {
            return [
                'code' => 400,
                'message' => 'Order ID is required',
                'payment' => null,
            ];
        }

        $payment = Payment::where('order_id', $orderId)->limit(1)->first();

        if (!$payment) {
            return [
            'code' => 404,
            'message' => 'Payment not found',
            'payment' => null,
            ];
        }

        return [
            'code' => 200,
            'message' => 'Payment found',
            'payment' => $payment,
        ];
    }
}
