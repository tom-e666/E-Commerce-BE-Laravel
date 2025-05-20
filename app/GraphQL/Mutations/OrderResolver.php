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
                    'code' => 404,
                    'message' => 'Cart is empty',
                    'order' => null,
                ];
            }
            DB::beginTransaction();
            try{
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'total_price' => 0,
                    'payment_status' => 'pending',
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
                
                return [
                    'code' => 200,
                    'message' => 'success',
                    'order' => $formattedOrder,
                ];
            }catch(\Exception $e){
                DB::rollBack();
                return [
                    'code' => 500,
                    'message' => 'Internal server error '.$e->getMessage(),
                    'order' => null,
                ];
            }
        }
        private function formatOrderResponse(Order $order): array
        {
            $order->load('items.product');
            
            // Convert product_id values to strings to match MongoDB storage format
            $productIds = $order->items->pluck('product_id')->map(function($id) {
                return (string)$id;  // Ensuring IDs are strings for MongoDB
            })->toArray();
            
            // Log raw product IDs for debugging
            Log::info('Raw product IDs', [
                'ids' => $order->items->pluck('product_id')->toArray()
            ]);
            
            $productDetails = ProductDetail::whereIn('product_id', $productIds)
                ->get();
            
            // Log the actual query results
            Log::info('Product details from MongoDB query', [
                'count' => $productDetails->count(),
                'details' => $productDetails->toArray()
            ]);
            
            // Index by product_id as string
            $productDetailsMap = $productDetails->keyBy('product_id');
            
            return [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $order->status,
                'total_price' => (float)$order->total_price,
                'payment_status' => $order->payment_status ?? 'pending',
                'shipping_address' => $order->shipping_address ?? null,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'items' => $order->items->map(function (OrderItem $item) use ($productDetailsMap) {
                    // Convert ID to string for MongoDB lookup
                    $productId = (string)$item->product_id;
                    $details = $productDetailsMap->get($productId);
                    
                    // Safe image extraction with multiple fallbacks
                    $image = '/defaultProduct.jpg';
                    if ($details) {
                        if (isset($details->images)) {
                            if (is_array($details->images) && !empty($details->images)) {
                                $image = $details->images[0];
                            } elseif (is_string($details->images)) {
                                // Attempt to parse JSON string
                                $imagesArray = json_decode($details->images, true);
                                if (is_array($imagesArray) && !empty($imagesArray)) {
                                    $image = $imagesArray[0];
                                }
                            }
                        }
                    }
                    
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'price' => (float)$item->price,
                        'quantity' => $item->quantity,
                        'name' => $item->product ? $item->product->name : 'Unknown Product',
                        'image' => $image,
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
        public function confirmOrder($_,array $args):array
        {
            $user = AuthService::Auth();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            if (!isset($args['order_id'])) {
                return $this->error('order_id is required', 400);
            }
            
            $order = Order::find($args['order_id']);
            if ($order === null) {
                return $this->error('Order not found', 404);
            }
            
            if (Gate::denies('view', $order)) {
                return $this->error('You are not authorized to view this order', 403);
            }
            
            return $this->success([
                'order' => $order->load('items.product'),
            ], 'Success', 200);
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
            
            $order->status = 'cancelled';
            $order->save();
            
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
            
            $order->status = 'shipped';
            $order->save();
            
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
        
        $order->status = 'delivered';
        $order->save();
        
        return $this->success([
            'order' => $order->load('items.product'),
        ], 'Order delivered successfully', 200);
    }


}