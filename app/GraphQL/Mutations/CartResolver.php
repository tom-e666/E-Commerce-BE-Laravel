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
            'user_id'=>'required|string',
            'product_id'=>'required|array',
            'quantity'=>'required|array',
        ]);
        if($validator->fails()){
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'cart' => null,
            ];
        }
        
        $cart = Cart::find($args['user_id']);
        if ($cart === null) {
            return [
                'code' => 404,
                'message' => 'Cart not found',
                'cart' => null,
            ];
        }
        //map stock first
        //map product_id later?
        $error;
        $products;
        $quantities;
        for($i=0;$i<count($args['product_id']);$i++){
            $product=Product::find($args['product_id'][$i]);
            if($product===null){
                $error='Product with id: ' . $args['product_id'][$i] . ' not found';
                continue;
            }
            $products[]=$product;
            $quantities[]=min($product->stock,$args['quantity'][$i]);
        }
        $cart.update([
            'product_id'=>$products,
            'quantity'=>$quantities,
        ]);
        return [
            'code' => 200,
            'message' => 'success',
            'cart' => $cart,
        ];
    }
}
