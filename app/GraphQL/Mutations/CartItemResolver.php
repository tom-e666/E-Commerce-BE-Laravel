<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\CartItem;
use Illuminate\Support\Facades\Validator;
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
    public function updateCart($_, array $args)
    {
        $validator=Validator::make($args,[
            // 'user_id'=>'required|string|exists:user_credentials,user_id',
            'product_id'=>'required|string|exists:products,id',
            'quantity'=>'required|numeric',
        ]);
        if($validator->fails()){
            return $this->error($validator->errors()->first(), 400);
        }

        $user = AuthService::Auth();
        if(!$user){
            return $this->error('Unauthorized', 401);
        } 
        
        try{
            // $cartItem = CartItem::where('user_id', $args['user_id']).where('product_id',$args['product_id'])->first();
            $cartItem = CartItem::where('user_id', $user->id)->where('product_id',$args['product_id'])->first();
            if ($cartItem) {
                $cartItem->quantity += $args['quantity'];
                $cartItem->save();
            }else{
                $cartItem = CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $args['product_id'],
                    'quantity' => $args['quantity'],
                ]);
            }
            
            return $this->success(['item' => $cartItem, ], 'CartItem updated successfully', 200);
        }catch(\Exception $e){
            return $this->error('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function deleteItem($_, array $args)
    {
        $validator=Validator::make($args,[
            // 'user_id'=>'required|string|exists:usersCredentials,user_id',
            'product_id'=>'required|string|exists:products,id',
        ]);
        if($validator->fails()){
            return $this->error($validator->errors()->first(), 400);
        }

        $user = AuthService::Auth();
        if(!$user){
            return $this->error('Unauthorized', 401);
        } 
        
        try{
            $cartItem = CartItem::where('user_id', $user->id)->where('product_id',$args['product_id'])->first();
            if (!$cartItem) {
                return $this->error('CartItem not found', 404);
            }
            
            $cartItem->delete();
            
            return $this->success(null, 'CartItem deleted successfully', 200);
        }catch(\Exception $e){
            return $this->error('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
