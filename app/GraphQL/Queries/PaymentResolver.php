<?php 
namespace App\GraphQL\Queries;
use App\Models\Payment;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;

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
    public function getAllPayments($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'payments' => null
            ];
        }
        
        $payments = Payment::all();
        
        // Use policy for authorization
        if (Gate::denies('viewAny', Payment::class)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to view these payments',
                'payments' => null
            ];
        }
        
        // Return all payments
        return [
            'code' => 200,
            'message' => 'Success',
            'payments' => $payments
        ];
    }
}
