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
    //need to config status of these guys
    public function createShipping($_,$args): array
    {
        $validator=Validator::make($args,[
            'order_id'=>'required|exists:orders,id',
            'carrier'=>'in:GHN,GRAB,SHOP',
            'note'=>'',
            'address'=>'required',
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
        $user = AuthService::Auth(); // pre-handled by middleware
        $shipping = Shipping::create([
            'order_id' => $args['order_id'],
            'tracking_code' => $args['order_id'].hash('sha256', time()),
            'carrier' => $args['carrier'],
            'estimated_date' => now()->addDays(3),
            'status' =>'pending',
            'address' => $args['address'],
            'recipient_name' => $user->name,
            'recipient_phone' => $user->phone,
            'note' => $args['note']?? "",
        ]);
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
    public function updateShipping($_,$args): array
    {
        $validator=Validator::make($args,[
            'id'=>'required|exists:shippings,id',
            'address'=>'',
            'recipient_name'=>'',
            'recipient_phone'=>'',
            'note'=>'',
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
        $shipping->carrier = $args['carrier']?? $shipping->carrier;
        $shipping->estimated_date = $args['address']|| $args['carrier']? now()->addDays(3): $shipping->estimated_date;
        $shipping->address = $args['address']??$shipping->address;
        $shipping->recipient_name = $args['recipient_name']??$shipping->recipient_name;
        $shipping->recipient_phone = $args['recipient_phone']??$shipping->recipient_phone;
        $shipping->note = $args['note']??$shipping->note;
        $shipping->save();
        return [
            'code' => 200,
            'message' => 'success',
        ];
    }
}
