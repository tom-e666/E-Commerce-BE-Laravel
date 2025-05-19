<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Models\Shipping;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;
use App\Models\Order;
use App\Services\AuthService;
use App\Services\GHNService;
use Illuminate\Support\Facades\Gate;

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
        if (Gate::denies('create', [$order])) {
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
            if($shipping->shipping_method==='GHN'){
                $response = $this->ghnService->cancelShippingOrder($shipping->ghn_order_code);
                if (isset($response['code']) && $response['code'] === 200) {
                    $shipping->status = 'cancelled';
                    $shipping->save();
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
        
        $shipping->status = $args['status'];
        $shipping->save();
        
        if ($args['status'] === 'delivered') {
            $order = Order::find($shipping->order_id);
            if ($order) {
                $order->status = 'delivered';
                $order->save();
            }
        }
        
        return [
            'code' => 200,
            'message' => 'Shipping status updated successfully',
            'shipping' => $shipping
        ];
    }

}
