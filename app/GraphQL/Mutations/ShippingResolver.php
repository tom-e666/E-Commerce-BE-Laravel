<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

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
            'order_id'=>'required',
            'tracking_code'=>'required',
            'carrier'=>'required',
            'estimated_date'=>'required',
            'status'=>'required',
        ]);
        if(validator->fails)
        {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'shipping' => null,
            ];
        }
        $shipping = Shipping::where('order_id',$args['order_id'])->first();
        if($shipping === null)
        {
            return [
                'code' => 404,
                'message' => 'Shipping not found',
                'shipping' => null,
            ];
        }
        $shipping->update([
            'tracking_code' => $args['tracking_code']?? $shipping->tracking_code,
            'carrier' => $args['carrier']?? $shipping->carrier,
            'estimated_date' => $args['estimated_date']?? $shipping->estimated_date,
            'status' => $args['status']?? $shipping->status,
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'shipping' => $shipping,
        ];
    }
}
