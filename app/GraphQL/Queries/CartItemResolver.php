<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\CartItem;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;

final readonly class CartItemResolver
{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function getCartItems($_, array $args)
    {
        $user = AuthService::Auth();//middleware
        if(!$user){
            return $this->error('Unauthorized', 401);
        }
         $cartItems = CartItem::where('user_id', $user->id)
        ->select('id', 'product_id', 'quantity', 'updated_at') 
        ->with('product')
        ->orderBy('updated_at','desc')
        ->get();
        if($cartItems->isEmpty()){
            return $this->success(null,'No items in cart', 200);
        }
        $formattedCartItems = $cartItems->map(function ($item) {
            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'product' => [
                    'product_id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'stock' => $item->product->stock,
                    'status' => $item->product->status,
                    'image' => $item->product->details ? $item->product->details->images[0] : null,
                ],
            ];
        });
        return $this->success([
            'cart_items' => $formattedCartItems,
        ], 'success', 200);
    }

}
