<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AuthService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

final readonly class OrderResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    
    public function getOrder($_, array $args): array
    {
        if(!isset($args['order_id']))
        {
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'orderItems' => null,
            ];
        }
        $order = Order::find($args['order_id']);
        if($order===null)
        {
            return [
                'code' => 404,
                'message' => 'order not found',
                'order' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'order' => $order->load('items'),
        ];
    }
    public function getOrdersfromUser($_, array $args): array
    {
        if(!isset($args['user_id']))
        {
            return [
                'code' => 400,
                'message' => 'user_id is required',
                'orders' => null,
            ];
        }
        $orders = Order::where('user_id',$args['user_id'])->get();
        if($orders===null){
            return[
                'code' => 404,
                'message' => 'orders not found',
                'orders' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'orders' => $orders->load('items'),
        ];
    }
    public function getUserOrders($_, array $args): array
    {
        $user = AuthService::Auth();
        $orders = Order::where('user_id',$user->id)->get();
        if($orders===null){
            return[
                'code' => 404,
                'message' => 'user have no orders',
                'orders' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'orders' => $orders->load('items'),
        ];
    }   
}
