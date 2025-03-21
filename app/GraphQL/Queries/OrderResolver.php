<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

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
        if($order===null){
            return[
                'code' => 404,
                'message' => 'order not found',
                'orderItems' => null,
            ];
        }
        if($order!==null)
        {
            $orderItems=OrderItem::where('order_id',$args['order_id'])->get();
            return [
                'code' => 200,
                'message' => 'success',
                'orderItems' => $orderItems,
                'order' => $order,
            ];
        }
        return [
            'code' => 404,
            'message' => 'order not found',
            'orderItems' => null,
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
        if($orders!==null)
        {
            return [
                'code' => 200,
                'message' => 'success',
                'orders' => $orders,
            ];
        }
        return [
            'code' => 404,
            'message' => 'orders not found',
            'orders' => null,
        ];
    }
}
