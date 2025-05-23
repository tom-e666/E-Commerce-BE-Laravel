<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Models\Shipping;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;
use App\Models\Order;
use App\Services\AuthService;
use App\Services\GHNService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

final readonly class ShippingResolver
{
    protected GHNService $ghnService;

    public function __construct(GHNService $ghnService)
    {
        $this->ghnService = $ghnService;
    }
    //need to config status of these guys
    public function  createShipping($_, array $args)
    {
        $user= AuthService::Auth(); // pre-handled by middleware
        $validator=Validator::make($args,[
            'order_id'=>'required|exists:orders,id',
            'province_name'=>'required|string',
            'district_name'=>'required|string',
            'ward_name'=>'required|string',
            'address'=>'required|string',
            'recipient_name'=>'required|string',
            'recipient_phone'=>'required|string',
            'note'=>'string',
            'shipping_method'=>'required|string|in:GHN,SHOP',
        ]);
        if($validator->fails()){
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'shipping' => null,
            ];
        }
        $order = Order::find($args['order_id']);
        if (!$order) {
            return [
                'code' => 404,
                'message' => 'Order not found',
                'shipping' => null,
            ];
        }
        // Fix: Pass the Shipping::class as the first parameter for policy lookup
        if (Gate::denies('create', [Shipping::class, $order])) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to create shipping for this order',
                'shipping' => null,
            ];
        }
        if($args['shipping_method'] === 'SHOP'){
            $shipping = Shipping::create([
                'order_id' => $args['order_id'],
                'status' => 'pending',
                'address' => $args['address'],
                'recipient_name' => $args['recipient_name'],
                'recipient_phone' => $args['recipient_phone'],
                'note' => $args['note'] ?? '',
                'province_name' => $args['province_name'],
                'district_name' => $args['district_name'],
                'ward_name' => $args['ward_name'],
                'shipping_fee' => 0,
                'expected_delivery_time' => now()->addDays(1),
                'shipping_method' => $args['shipping_method'],
            ]);
            return [
                'code' => 200,
                'message' => 'Shipping created successfully',
                'shipping' => $shipping,
            ];
        }
        if($args['shipping_method'] === 'GHN'){
            $shippingInfo =[
                'province_name' => $args['province_name'],
                'district_name' => $args['district_name'],
                'ward_name' => $args['ward_name'],
                'address' => $args['address'],
                'recipient_name' => $args['recipient_name'],
                'recipient_phone' => $args['recipient_phone'],
                'note' => $args['note'] ?? '',
                'order_id' => $args['order_id'],
                'shipping_method' => $args['shipping_method'],
                'weight' => $this->calculateShippingWeight($order),
                'value' => $order->total_price,
                'items' => $order->items->map(function($item) {
                    return [
                        'name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price
                    ];
                })->toArray(),
            ];
            $response = $this->ghnService->createShippingOrder($order,$shippingInfo);
            if (isset($response['code']) && $response['code'] === 200) {
                $shipping = Shipping::create([
                    'order_id' => $args['order_id'],
                    'shipping_method' => 'GHN',
                    'status' => 'pending',
                    'address' => $args['address'],
                    'recipient_name' => $args['recipient_name'],
                    'recipient_phone' => $args['recipient_phone'],
                    'note' => $args['note'] ?? '',
                    'ghn_order_code' => $response['data']['order_code'],
                    'province_name' => $args['province_name'],
                    'district_name' => $args['district_name'],
                    'ward_name' => $args['ward_name'],
                    'shipping_fee' => $response['data']['total_fee']??0,
                    'expected_delivery_time' => $response['data']['expected_delivery_time'],
                ]);
                return [
                    'code' => 200,
                    'message' => 'Shipping created successfully',
                    'shipping' => $shipping,
                ];
            }
            return [
                'code' => $response['code'] ?? 500,
                'message' => $response['message'] ?? 'Failed to create shipping',
                'shipping' => null,
            ];
        }
            
    }
    public function cancelShipping($_, array $args)
    {
        $shipping = Shipping::find($args['shipping_id']);
        if (!$shipping) {
            return [
                'code' => 404,
                'message' => 'Shipping not found',
            ];
        }
        if (Gate::denies('cancel', $shipping)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to cancel this shipping',
            ];
        }
            $order = Order::find($shipping->order_id);
            if (!$order) {
                return [
                    'code' => 404,
                    'message' => 'Order not found',
                ];
            }
            if($shipping->status==='delivered'){
                return [
                    'code' => 400,
                    'message' => 'Shipping has been delivered, cannot be cancelled',
                ];
            }
            // Use database transaction to ensure all operations succeed or fail together
            DB::beginTransaction();
            try {
                if($shipping->shipping_method==='GHN'){
                    $response = $this->ghnService->cancelShippingOrder($shipping->ghn_order_code);
                    if (isset($response['code']) && $response['code'] === 200) {
                        $shipping->status = 'cancelled';
                        $shipping->save();
                        
                        // Update order status and restore inventory
                        if (in_array($order->status, ['confirmed', 'processing', 'shipping'])) {
                            $order->status = 'cancelled';
                            $order->save();
                            
                            // Restore product inventory
                            foreach ($order->items as $item) {
                                $product = Product::find($item->product_id);
                                if ($product) {
                                    $product->stock += $item->quantity;
                                    $product->save();
                                }
                            }
                        }
                        
                        DB::commit();
                        return [
                            'code' => 200,
                            'message' => 'Shipping cancelled successfully',
                        ];
                    }
                    return [
                        'code' => $response['code'] ?? 500,
                        'message' => $response['message'] ?? 'Failed to cancel shipping',
                    ];
                }
                if($shipping->shipping_method==='SHOP'){
                    $shipping->status = 'cancelled';
                    $shipping->save();
                    return [
                        'code' => 200,
                        'message' => 'Shipping cancelled successfully',
                    ];
                }
                return [
                    'code' => 400,
                    'message' => 'Shipping method not supported for cancellation',
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                return [
                    'code' => 500,
                    'message' => 'An error occurred while cancelling shipping',
                ];
            }
        
    }
    private function calculateShippingWeight($order)
    {
        $totalWeight = 0;
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product) {
                $totalWeight += $product->weight * $item->quantity;
            }
        }
        return $totalWeight;
    }
    public function updateShippingStatus($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'shipping' => null
            ];
        }
        
        $shipping = Shipping::find($args['shipping_id']);
        if (!$shipping) {
            return [
                'code' => 404,
                'message' => 'Shipping not found',
                'shipping' => null
            ];
        }
        
        // Use policy for authorization
        if (Gate::denies('update', $shipping)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to update shipping status',
                'shipping' => null
            ];
        }
        
        // Validate status
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($args['status'], $validStatuses)) {
            return [
                'code' => 400,
                'message' => 'Invalid status value',
                'shipping' => null
            ];
        }
        
        DB::beginTransaction();
        try {
            $shipping->status = $args['status'];
            $shipping->save();
            
            // When shipping is delivered, update order status to delivered
            if ($args['status'] === 'delivered') {
                $order = Order::find($shipping->order_id);
                if ($order) {
                    $order->status = 'delivered';
                    $order->save();
                }
            }
            
            // When shipping is cancelled, update order and restore inventory
            if ($args['status'] === 'cancelled') {
                $order = Order::find($shipping->order_id);
                if ($order && in_array($order->status, ['confirmed', 'processing', 'shipping'])) {
                    $order->status = 'cancelled';
                    $order->save();
                    
                    // Restore product inventory
                    foreach ($order->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->stock += $item->quantity;
                            $product->save();
                        }
                    }
                }
            }
            
            DB::commit();
            return [
                'code' => 200,
                'message' => 'Shipping status updated successfully',
                'shipping' => $shipping
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'code' => 500,
                'message' => 'An error occurred while updating shipping status',
                'shipping' => null
            ];
        }
    }
    /**
     * Update shipping address and recipient information
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with shipping or error
     */
    public function updateShipping($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'shipping' => null
            ];
        }
        
        $shipping = Shipping::find($args['shipping_id']);
        if (!$shipping) {
            return [
                'code' => 404,
                'message' => 'Shipping not found',
                'shipping' => null
            ];
        }
        
        $order = Order::find($shipping->order_id);
        if (!$order) {
            return [
                'code' => 404,
                'message' => 'Associated order not found',
                'shipping' => null
            ];
        }
        
        // Use policy for authorization
        if (Gate::denies('update', $shipping)) {
            return [
                'code' => 403,
                'message' => 'You are not authorized to update this shipping information',
                'shipping' => null
            ];
        }
        
        // Prevent updates to delivered shipments
        if ($shipping->status === 'delivered' || $shipping->status === 'shipped') {
            return [
                'code' => 400,
                'message' => 'Cannot update shipping information for shipments that have already been shipped or delivered',
                'shipping' => null
            ];
        }
        
        // Update only the provided fields
        $updateData = [];
        if (isset($args['province_name'])) $updateData['province_name'] = $args['province_name'];
        if (isset($args['district_name'])) $updateData['district_name'] = $args['district_name'];
        if (isset($args['ward_name'])) $updateData['ward_name'] = $args['ward_name'];
        if (isset($args['address'])) $updateData['address'] = $args['address'];
        if (isset($args['recipient_name'])) $updateData['recipient_name'] = $args['recipient_name'];
        if (isset($args['recipient_phone'])) $updateData['recipient_phone'] = $args['recipient_phone'];
        if (isset($args['note'])) $updateData['note'] = $args['note'];
        
        // If shipping method is GHN and address changed, update shipping info with GHN API
        if ($shipping->shipping_method === 'GHN' && 
            (isset($args['province_name']) || isset($args['district_name']) || 
             isset($args['ward_name']) || isset($args['address']))) {
            
            // Get updated shipping address information
            $shippingInfo = [
                'province_name' => $args['province_name'] ?? $shipping->province_name,
                'district_name' => $args['district_name'] ?? $shipping->district_name,
                'ward_name' => $args['ward_name'] ?? $shipping->ward_name,
                'address' => $args['address'] ?? $shipping->address,
                'recipient_name' => $args['recipient_name'] ?? $shipping->recipient_name,
                'recipient_phone' => $args['recipient_phone'] ?? $shipping->recipient_phone,
                'note' => $args['note'] ?? $shipping->note,
                'order_id' => $shipping->order_id,
                'shipping_method' => $shipping->shipping_method,
                'weight' => $this->calculateShippingWeight($order),
                'value' => $order->total_price,
            ];
            
            // Update with GHN API
            $response = $this->ghnService->updateShippingOrder($shipping->ghn_order_code, $shippingInfo);
            
            if (isset($response['code']) && $response['code'] === 200) {
                // If API update was successful, update fee if changed
                if (isset($response['data']['total_fee'])) {
                    $updateData['shipping_fee'] = $response['data']['total_fee'];
                }
                if (isset($response['data']['expected_delivery_time'])) {
                    $updateData['expected_delivery_time'] = $response['data']['expected_delivery_time'];
                }
            } else {
                return [
                    'code' => $response['code'] ?? 500,
                    'message' => $response['message'] ?? 'Failed to update shipping with carrier',
                    'shipping' => $shipping,
                ];
            }
        }
        
        $shipping->update($updateData);
        
        return [
            'code' => 200,
            'message' => 'Shipping information updated successfully',
            'shipping' => $shipping->fresh()
        ];
    }

}
