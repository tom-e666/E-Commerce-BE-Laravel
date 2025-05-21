<?php declare(strict_types=1);

    namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\ProductDetail;
use App\Models\Product;
use App\Services\AuthService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\GraphQL\Traits\GraphQLResponse;
use Illuminate\Support\Facades\Log;
use App\Services\GHNService;
use App\Models\UserCredential;
use App\Models\Shipping;

final readonly class OrderResolver
{
    use GraphQLResponse;
    
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
    public function createOrder($_, array $args): array
    {
        $user = auth('api')->user();

        $validator = Validator::make($args, [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $total_price = 0;

            foreach ($args['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    DB::rollBack();
                    return $this->error('Product not found: ' . $item['product_id'], 404);
                }

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return $this->error('Not enough stock for product: ' . $product->name, 400);
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $product->stock -= $item['quantity'];
                $product->save();

                $total_price += $product->price * $item['quantity'];
            }

            $order->total_price = $total_price;
            $order->save();

            DB::commit();

            // Remove items from cart if typeOrder is 'cart'
            if (isset($args['typeOrder']) && $args['typeOrder'] === 'cart') {
                foreach ($args['items'] as $item) {
                    $cartItem = $user->cartItems()
                        ->where('product_id', $item['product_id'])
                        ->first();
                    
                    if ($cartItem) {
                        // Update quantity or delete if quantity is 0
                        if (isset($item['quantity']) && $item['quantity'] > 0) {
                            $cartItem->quantity -= $item['quantity'];
                            $cartItem->save();
                        } else {
                            $cartItem->delete();
                        }
                    }
                }
            }

            return $this->success([
                'orders' => $this->formatOrderResponse($order),
            ], 'Order created successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Internal server error: ' . $e->getMessage(), 500);
        }
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

        // Khôi phục lại stock cho từng sản phẩm trong order
        foreach ($order->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock += $item->quantity;
                $product->save();
            }
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
        $user = auth('api')->user();
        $cartItems = CartItem::where('user_id', $user->id)->get();
        if($cartItems->isEmpty()){
            return $this->error('Cart is empty', 404);
        }
        DB::beginTransaction();
        try{
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => 0,
            ]);
            $total_price = 0;
            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem->product_id);
                if($product === null){
                    continue;
                }
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->price,
                ]);
                $total_price += $product->price * $cartItem->quantity;
            }
            $order->total_price = $total_price;
            $order->save();
            $formattedOrder = $this->formatOrderResponse($order);
            DB::commit();
            
            return $this->success([
                'order' => $formattedOrder,
            ], 'Order created successfully', 200);
        }catch(\Exception $e){
            DB::rollBack();
            return $this->error('Internal server error: ' . $e->getMessage(), 500);
        }
    }
    private function formatOrderResponse(Order $order): array
    {
        $order->load('items.product.details');
        Log::info('Order details from MySQL query', [
            'count' => $order->items->count(),
            'details' => $order->items->toArray()
        ]);

        // Index by product_id as string            
        return [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'total_price' => (float)$order->total_price,
            'payment_status' => $order->payment_status ?? 'pending',
            'shipping_address' => $order->shipping_address ?? null,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'items' => $order->items->map(function (OrderItem $item) {

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'price' => (float)$item->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product ? $item->product->name : 'Unknown Product',
                    'image' => $item->product && $item->product->details ? $item->product->details->images[0] : null,
                ];
            }),
        ];
    }
    private function updateProductStock(OrderItem $orderItem): void
    {
        $product = Product::find($orderItem->product_id);
        if ($product) {
            $product->stock -= $orderItem->quantity;
            $product->save();
        }
    }
    private function restoreProductStock(OrderItem $orderItem): void
    {
        $product = Product::find($orderItem->product_id);
        if ($product) {
            $product->stock += $orderItem->quantity;
            $product->save();
        }
    }

    public function processingOrder($_,array $args):array
    {
        $user = auth('api')->user();
        
        // Only admin or staff can process orders
        if (!$user->isAdmin() && !$user->isStaff()) {
            return $this->error('Unauthorized', 403);
        }
        
        if (!isset($args['order_id'])) {
            return $this->error('order_id is required', 400);
        }
        
        $order = Order::find($args['order_id']);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        if ($order->status !== 'confirmed') {
            return $this->error('Order must be confirmed before processing', 400);
        }
        
        $order->status = 'processing';
        $order->save();
        
        return $this->success([], 'Order processed successfully', 200);
    }

    public function cancelOrder($_,array $args):array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Only admin or staff can cancel orders
        if (!$user->isAdmin() && !$user->isStaff()) {
            return $this->error('Unauthorized', 403);
        }
        
        if (!isset($args['order_id'])) {
            return $this->error('order_id is required', 400);
        }
        
        $order = Order::find($args['order_id']);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        if(!in_array($order->status, ['pending', 'confirmed'])) {
            return $this->error('Order cannot be cancelled at this stage', 400);
        }

        foreach ($order->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock += $item->quantity;
                $product->save();
            }
        }

        if($order->payment){
            $order->payment->payment_status = 'refunded';
            $order->payment->save();
        }

        if($order->shipping){
            $order->shipping->status = 'cancelled';
            $order->shipping->save();
        }
        
        return $this->success([
            'order' => $order->load('items.product'),
        ], 'Order cancelled successfully', 200);
    }
    public function shipOrder($_,array $args):array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Only admin or staff can ship orders
        if (!$user->isAdmin() && !$user->isStaff()) {
            return $this->error('Unauthorized', 403);
        }
        
        if (!isset($args['order_id'])) {
            return $this->error('order_id is required', 400);
        }
        
        $order = Order::find($args['order_id']);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        if ($order->status !== 'confirmed') {
        return $this->error('Order must be confirmed before shipping', 400);
    }
        
        $order->status = 'shipping';
        $order->save();

        // Update shipping status if applicable
        if ($order->shipping) {
            $order->shipping->status = 'delivering';
            $order->shipping->save();
        }
        
        return $this->success([
            'order' => $order->load('items.product'),
        ], 'Order shipped successfully', 200);
    }
    public function deliverOrder($_,array $args):array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Only admin or staff can deliver orders
        if (!$user->isAdmin() && !$user->isStaff()) {
            return $this->error('Unauthorized', 403);
        }
        
        if (!isset($args['order_id'])) {
            return $this->error('order_id is required', 400);
        }
        
        $order = Order::find($args['order_id']);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }

        if ($order->status !== 'shipping') {
            return $this->error('Order must be shipping before completed', 400);
        }
        
        $order->status = 'completed';
        $order->save();
        
        return $this->success([
            'order' => $order->load('items.product'),
        ], 'Order completed successfully', 200);
    }


    }