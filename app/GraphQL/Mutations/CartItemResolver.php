<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

final readonly class CartResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function updateCart($_, array $args)
    {
        $validator=Validator::make($args,[
            'user_id'=>'required|string|exists:usersCredentials,user_id,',
            'product_id'=>'required|string|exists:products,id',
            'quantity'=>'required|number',
        ]);
        if($validator->fails()){
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'cartItem' => null,
            ];
        }
        
        $cartItem = CartItem::where('user_id', $args['user_id']).where('product_id',$args['product_id'])->first();
        if ($cartItem === null) {
            return [
                'code' => 404,
                'message' => 'CartItem not found',
                'cartItem' => null,
            ];
        }

        $cartItem->quantity = $args['quantity'];
        
        return [
            'code' => 200,
            'message' => 'success',
            'cartItem' => $cartItem,
        ];
    }
}
