<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Shipping;

final readonly class ShippingResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function getShipping($_, array $args)
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'shipping' => null,
            ];
        }
        $shipping = Shipping::where($args['order_id'])->first();
        if ($shipping === null) {
            return [
                'code' => 404,
                'message' => 'Shipping not found',
                'shipping' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'shipping' => $shipping,
        ];
    }
}
