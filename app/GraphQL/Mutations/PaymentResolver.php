<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

final readonly class PaymentResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function createPayment($_, array $args)
    {
        $validator=Validator::make($args,[
            'order_id'=>'required|string',
            'payment_method'=>'required|"credit_card"|"cod"|"bank_transfer"|"e_wallet"',
            'payment_status'=>'required|"pending"|"completed"|"failed"|"refunded"',
        ]);
        if($validator->fails()){
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'payment' => null,
            ];
        }
        $payment = Payment::create([
            'order_id' => $args['order_id'],
            'payment_method' => $args['payment_method'],
            'payment_status' => $args['payment_status'],
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'payment' => $payment,
        ];
    }
}
