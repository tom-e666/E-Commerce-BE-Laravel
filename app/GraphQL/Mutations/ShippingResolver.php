<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Models\Shipping;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;
final readonly class ShippingResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function updateShipping($_,$args): array
    {
        $validator=Validator::make($args,[
            'order_id'=>'required|exists:orders,id',
            'tracking_code'=>'',
            'carrier'=>'',
            'estimated_date'=>'',
            'status'=>'',
        ]);
        if($validator->fails())
        {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
            ];
        }
        $shipping = Shipping::where('order_id',$args['order_id'])->first();
        if($shipping === null)
        {
            return [
                'code' => 404,
                'message' => 'Shipping not found',
            ];
        }
        $shipping->tracking_code = $args['tracking_code']?? $shipping->tracking_code;
        $shipping->carrier = $args['carrier']?? $shipping->carrier;
        $shipping->estimated_date = $args['estimated_date']?? $shipping->estimated_date;
        $shipping->status = $args['status']?? $shipping->status;
        $shipping->save();
        return [
            'code' => 200,
            'message' => 'success',
            'shipping' => $shipping,
        ];
    }
    //need to config status of these guys
    public function createShipping($_,$args): array
    {
        $validator=Validator::make($args,[
            'order_id'=>'required|exists:orders,id',
            'tracking_code'=>'',
            'carrier'=>'',
            'estimated_date'=>'',
            'status'=>'',
        ]);
        if($validator->fails())
        {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
            ];
        }
        $existingShipping = Shipping::where('order_id', $args['order_id'])->first();
        if ($existingShipping) {
            return [
                'code' => 400,
                'message' => 'Shipping already exists for this order',
            ];
        }
        $shipping = Shipping::create($args);
        return [
            'code' => 200,
            'message' => 'success',
        ];
    }
    public function updateShippingStatus($_,$args): array
    {
        $validator=Validator::make($args,[
            'id'=>'required|exists:shippings,id',
            'status'=>'required|in:packed,shipped,delivered,cancelled',
        ]);
        if($validator->fails())
        {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
            ];
        }
        $shipping = Shipping::find($args['id']);
        if($shipping === null)
        {
            return [
                'code' => 404,
                'message' => 'Shipping not found',
            ];
        }
        $shipping->status = $args['status'];
        $shipping->save();
        return [
            'code' => 200,
            'message' => 'success',
        ];
    }
        
}
