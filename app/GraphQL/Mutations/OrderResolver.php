<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Product;
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
    public function updateOrderItem($_,array $args):array
    {
        $user=AuthService::Auth(); // pre-handled by middleware
        if(!$user){
            return [
                'code' => 401,
                'message' => 'Unauthorized',
            ];
        }
        if(!isset($args['order_item_id'])){
            return [
                'code' => 400,
                'message' => 'order_item_id is required',
            ];
        }
        $orderItem=OrderItem::find($args['order_item_id']);
        if($orderItem===null || $orderItem->order->user_id !== $user->id){
            return [
                'code' => 404,
                'message' => 'orderItem not found',
            ];
        }
        $orderItem->quantity = $args['quantity'] ?? $orderItem->quantity;
        $orderItem->save();
        return [
            'code' => 200,
            'message' => 'success',
        ];
    }
    public function deleteOrderItem($_,array $args):array
    {
        $user=AuthService::Auth(); // pre-handled by middleware
        if(!$user){
            return [
                'code' => 401,
                'message' => 'Unauthorized',
            ];
        }
        if(!isset($args['order_item_id'])){
            return [
                'code' => 400,
                'message' => 'order_item_id is required',
            ];
        }
        $orderItem=OrderItem::find($args['order_item_id']);
        if($orderItem===null || $orderItem->order->user_id !== $user->id){
            return [
                'code' => 404,
                'message' => 'orderItem not found',
            ];
        }
        $orderItem->delete();
        return [
            'code' => 200,
            'message' => 'success',
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
        $total_price= OrderItem::where('order_id',$args['order_id'])->selectRaw('SUM(price * quantity) as total_price')->value('total_price')??0;
        $order= Order::create([
            'user_id' => $args['user_id'],
            'status' => $args['status'],
            'total_price' => $args['total_price']??0,
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
        $order->status=$args['status'] ?? $order->status;
        $order->price=$args['total_price'] ?? $order->total_price;
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
            'code' => 200,
           'message' => 'success',
            'order' => null,
        ];
    }
    public function createOrderFromCart($_,array $args):array
    {
        $user = AuthService::Auth(); // pre-handled by middleware
        if(!$user){
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'order' => null,
            ];
        }
        $cartItems = CartItem::where('user_id', $user->id)->get();
        if($cartItems->isEmpty()){
            return [
                'code' => 400,
                'message' => 'Cart is empty',
                'order' => null,
            ];
        }
        $total_price = 0;
        foreach ($cartItems as $item) {
            $total_price += $item->product->price * $item->quantity;
        }
        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => $total_price,
            ]);
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }
            CartItem::where('user_id', $user->id)->delete();
            DB::commit();
            return [
                'code' => 200,
                'message' => 'Order created successfully',
                'order' => $order->load('items'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'order' => null,
            ];
        }
    }
    function cancelOrder($_,array $args):array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
            ];
        }
        $user=AuthService::Auth(); // pre-handled by middleware
        $order= Order::where('id',$args['order_id'])->where('user_id',$user->id)->first();
        if($order===null){
            return [
                'code' => 404,
                'message' => 'order not found',
=            ];
        }
        $order->status='cancelled';
        $order->save();
        try {
            DB::beginTransaction();
            $orderItems=OrderItem::where('order_id',$args['order_id'])->get();
            foreach($orderItems as $item){
                CartItem::create([
                    'user_id'=>$user->id,
                    'product_id'=>$item->product_id,
                    'quantity'=>$item->quantity,
                ]);
            }
            DB::commit();
            return [
                'code' => 200,
                'message' => 'Order cancelled successfully',
=            ];
        }catch(\Exception $e){
                DB::rollBack();
                return [
                    'code' => 500,
                    'message' => 'Failed to cancel order',
                ];
            }
    }
    function confirmOrder($_,array $args):array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
            ];
        }
        $user=AuthService::Auth(); // pre-handled by middleware

        $order=Order::find($args['order_id'])->where('user_id',$user->id)->first();
        if($order===null){
            return [
                'code' => 404,
                'message' => 'order not found',
            ];
        }
        $order->status='confirmed';
        $order->save();
        return [
            'code' => 200,
            'message' => 'success',
        ];
    }
    function shipOrder($_,array $args):array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required'
            ];
        }
        $user=AuthService::Auth(); // pre-handled by middleware
        $order=Order::where('id',$args['order_id'])->where('user_id',$user->id)->first();

        if($order===null){
            return [
                'code' => 404,
                'message' => 'order not found',
            ];
        }
        $order->status='shipped';
        $order->save();
        return [
            'code' => 200,
            'message' => 'success',
        ];
    }
    function deliverOrder($_,array $args):array
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
            ];
        }
        $user=AuthService::Auth(); // pre-handled by middleware
        $order=Order::where('id',$args['order_id'])->where('user_id',$user->id)->first();
        if($order===null){
            return [
                'code' => 404,
                'message' => 'order not found',
            ];
        }
        $order->status='delivered';
        $order->save();
        return [
            'code' => 200,
            'message' => 'success',
        ];
    }
}
