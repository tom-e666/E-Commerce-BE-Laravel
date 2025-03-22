<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

final readonly class CartResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
  
    public function getCartItems($_, array $args)
    {
        if(!isset($args['user_id'])){
            return [
                'code' => 400,
                'message' => 'user_id is required',
                'cart_items' => null,
            ];
        }
        $user = User::find($args['user_id']);
        if ($user === null) {
            return [
                'code' => 404,
                'message' => 'User not found',
                'cart_items' => null,
            ];
        }
        $cartItems = CartItem::where('user_id', $args['user_id'])->get();
        return [
            'code' => 200,
            'message' => 'success',
            'cart_items' => $cartItems,
        ];
    }
}
