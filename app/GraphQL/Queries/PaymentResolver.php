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
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'payment' => null
            ];
        }
        
        $payment = Payment::where('order_id', $args['order_id'])->first();
        if (!$payment) {
            return [
                'code' => 404,
                'message' => 'Payment not found',
                'payment' => null
            ];
        }
        
        // Use policy for authorization
        if (Gate::denies('view', $payment)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to view this payment',
                'payment' => null
            ];
        }
        
        // Return payment details
        return [
            'code' => 200,
            'message' => 'Success',
            'payment' => $payment
        ];
    }
    
}
