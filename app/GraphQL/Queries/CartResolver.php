<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

final readonly class CartResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function getCart($_, array $args)
    {
        $cart = Cart::where('user_id', $args['user_id'])->first();
        if ($cart === null) {
            return [
                'code' => 404,
                'message' => 'Cart not found',
                'cart' => null,
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'cart' => $cart,
        ];
    }
}
