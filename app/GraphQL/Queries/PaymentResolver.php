<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

final readonly class PaymentResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function getPayment($_, array $args)
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'user_id is required',
                'payment' => null,
            ];
        }
        $payment = Payment::where('order_id', $args['order_id'])->first();
        if ($payment === null) {
            return [
                'code' => 404,
                'message' => 'Payment not found',
                'payment' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'payment' => $payment,
        ];
    }
}
