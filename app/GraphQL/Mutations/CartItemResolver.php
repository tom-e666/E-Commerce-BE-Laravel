<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;
use App\Models\ProductDetail;
use App\Services\GHNService;
final class CartItemResolver
{
    use GraphQLResponse;
    /**
     * Add or update an item in the cart
     */
    public function updateCart($_, array $args)
    {
        $validator = Validator::make($args, [
            'product_id' => 'required|string|exists:products,id',
            'quantity' => 'required|numeric|min:1',
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        // Check product availability
        $product = Product::find($args['product_id']);
        if (!$product) {
            return $this->error('Product not found', 404);
        }
        
        if (!$product->status) {
            return $this->error('Product is not available for purchase', 400);
        }
        
        // Check if requested quantity exceeds available stock
        if ($args['quantity'] > $product->stock) {
            return $this->error("Cannot add {$args['quantity']} items. Only {$product->stock} in stock.", 400);
        }
        
       
        $cartItem = CartItem::where('user_id', $user->id)
                           ->where('product_id', $args['product_id'])
                           ->first();
        
        if ($cartItem) {
            // Check update permission using policy
            if (Gate::denies('update', $cartItem)) {
                return $this->error('You are not authorized to update this cart item', 403);
            }
            
            $cartItem->quantity += $args['quantity'];
            $cartItem->save();
        } else {
            // Check create permission using policy
            if (Gate::denies('update', CartItem::class)) {
                return $this->error('You are not authorized to create cart items', 403);
            }
            
            $cartItem = CartItem::create([
                'user_id' => $user->id,
                'product_id' => $args['product_id'],
                'quantity' => $args['quantity'],
            ]);
        }
        
        // Load product relation for the response
        $cartItem->load('product');
        
        return $this->success([
            'item' => $this->formatCartItemResponse($cartItem),
        ], 'Cart item updated successfully', 200);
    }

    /**
     * Remove an item from the cart
     */
    public function deleteCartItem($_, array $args)
    {
        $validator = Validator::make($args, [
            'product_id' => 'required|string|exists:products,id',
        ]);
        
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $cartItem = CartItem::where('user_id', $user->id)
                           ->where('product_id', $args['product_id'])
                           ->first();
                           
        if (!$cartItem) {
            return $this->error('Cart item not found', 404);
        }
        
        // Check delete permission using policy
        if (Gate::denies('delete', $cartItem)) {
            return $this->error('You are not authorized to delete this cart item', 403);
        }
        
        $cartItem->delete();
        
        return $this->success([], 'Cart item removed successfully', 200);
    }
    
    /**
     * Clear all items from the cart
     */
    public function clearCart($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check clear permission using policy
        if (Gate::denies('clear', CartItem::class)) {
            return $this->error('You are not authorized to clear your cart', 403);
        }
        
        CartItem::where('user_id', $user->id)->delete();
        
        return $this->success([], 'Cart cleared successfully', 200);
    }
    
    /**
     * Get all items in the user's cart
     */
    public function getCartItems($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $cartItems = CartItem::where('user_id', $user->id)
                           ->with('product')
                           ->get();

        // Filter out items the user doesn't have permission to view
        $cartItems = $cartItems->filter(function($item) use ($user) {
            return Gate::allows('view', $item);
        });

        $formattedItems = $cartItems->map(function($item) {
            return $this->formatCartItemResponse($item);
        });

        return $this->success([
            'cart_items' => $formattedItems,
        ], 'Success', 200);
    }

    /**
     * Format cart item for response
     */
    private function formatCartItemResponse($cartItem)
    {
        $product = $cartItem->product;
        
        // Get product details from MongoDB for consistent image handling
        $productDetail = ProductDetail::where('product_id', (string)$product->id)->first();
        
        // Get image with proper fallback handling
        $image = null;
        if ($productDetail && !empty($productDetail->images)) {
            if (is_string($productDetail->images)) {
                $imagesArray = json_decode($productDetail->images, true);
                $image = $imagesArray[0] ?? null;
            } else {
                $image = $productDetail->images[0] ?? null;
            }
        }
        
        return [
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'product' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float)$product->price,
                'image' => $image ?? $product->image_url ?? null,  // Use MongoDB image or fallback
                'stock' => (int)$product->stock,
                'status' => (bool)$product->status
            ]
        ];
    }
}