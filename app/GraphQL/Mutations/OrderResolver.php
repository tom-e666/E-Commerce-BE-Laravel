<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
/**
    order:
    create
    update delete
    
    
    orderItem:
    update
    delete
    create
 */
final readonly class OrderResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function updateOrderItem($_,$args): array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'orderItem' => null,
            ];
        }
        if(!isset($args['product_id'])){
            return [
                'code' => 400,
                'message' => 'product_id is required',
                'orderItem' => null,
            ];
        }
        $orderItem= OrderItem::where('order_id',$args['order_id'])->where('product_id',$args['product_id'])->first();
        if($orderItem===null){
            return [
                'code' => 404,
                'message' => 'orderItem not found',
                'orderItem' => null,
            ];
        }
        $orderItem=[
            'quantity'=>$args['quantity'] ?? $orderItem->quantity,
            'price'=>$args['price'] ?? $orderItem->price,
        ];
        $orderItem->save();
    }
    public function deleteOrderItem($_,array $args):array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'orderItem' => null,
            ];
        }
        if(!isset($args['product_id'])){
            return [
                'code' => 400,
                'message' => 'product_id is required',
                'orderItem' => null,
            ];
        }
        $orderItem= OrderItem::where('order_id',$args['order_id'])->where('product_id',$args['product_id'])->first();
        if($orderItem===null){
            return [
                'code' => 404,
                'message' => 'orderItem not found',
                'orderItem' => null,
            ];
        }
        $orderItem->delete();
        return [
            'code' => 200,
            'message' => 'success',
            'orderItem' => $orderItem,
        ];
    }
    public function createOrderItem($_,array $args):array
    {
        $validator = Validator::make($args, [
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'orderItem' => null,
            ];
        }
        $orderItem = OrderItem::create([
            'order_id' => $args['order_id'],
            'product_id' => $args['product_id'],
            'quantity' => $args['quantity'],
            'price' => $args['price'],
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'orderItem' => $orderItem,
        ];
    }
    public function createOrder($_,array $args):array
    {
        $validator = Validator::make($args, [
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled',
            'total_price' => 'required|numeric|min:0',
        ]);
        if($validator->fails)
        {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'order' => null,
            ];
        }
        $total_price=OrderItem::where('order_id',$args['order_id'])->sum('price'*'quantity');
        $order= Order::create([
            'user_id' => $args['user_id'],
            'status' => $args['status'],
            'total_price' => $args['total_price'],
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'order' => $order,
        ];
    }
    public function updateOrder($_,array $args):array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'order' => null,
            ];
        }
        $order=Order::find($args['order_id']);
        if($order===null){
            return [
                'code' => 404,
                'message' => 'order not found',
                'order' => null,
            ];
        }
        $order=[
            'status'=>$args['status'] ?? $order->status,
            'total_price'=>$args['total_price'] ?? $order->total_price,
        ];
        $order->save();
        return [
            'code' => 200,
            'message' => 'success',
            'order' => $order,
        ];
    }
    public function deleteOrder($_,array $args):array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'order' => null,
            ];
        }
        if(!isset($args['user_id'])){
            return [
                'code' => 400,
                'message' => 'user_id is required',
                'order' => null,
            ];
        }
        $order=Order::where('id',$args['order_id'])->where('user_id',$args['user_id'])->first();
        if($order===null){
            return [
                'code' => 404,
                'message' => 'order not found',
                'order' => null,
            ];
        }
        $order->delete();   
        return [
            code => 200,
            message => 'success',
            order => null,
        ];
    }
}
