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
    public function getShippingByOrder($_, array $args)
    {
        if(!isset($args['order_id'])){
            return [
                'code' => 400,
                'message' => 'order_id is required',
                'shipping' => null,
            ];
        }
        $shipping = Shipping::where('order_id', $args['order_id'])->first();
        if ($shipping === null) {
            return [
                'code' => 404,
                'message' => 'Shipping not found for the given order_id',
                'shipping' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'shipping' => $shipping,
        ];
    }

    public function getShippings($_, array $args)
    {
        $query = Shipping::query();

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }

        $shipping = $query->orderBy('created_at', 'desc')->get();

        if ($shipping === null) {
            return [
                'code' => 404,
                'message' => 'Shipping not found matching the criteria',
                'shippings' => null,
            ];
        }

        return [
            'code' => 200,
            'message' => 'success',
            'shippings' => $shipping,
        ];
    }
}