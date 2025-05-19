<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\CartItem;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;

final class CartItemResolver
{
    use GraphQLResponse;

    /**
     * Get all items in the user's cart
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with cart items or error
     */
    public function getCartItems($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $cartItems = CartItem::where('user_id', $user->id)
            ->select('id', 'product_id', 'quantity', 'updated_at') 
            ->with(['product' => function($query) {
                $query->with('details'); // Eager load product details
            }])
            ->orderBy('updated_at', 'desc')
            ->get();
        
        if ($cartItems->isEmpty()) {
            return $this->success([
                'cart_items' => [], // Return empty array instead of null for consistency
            ], 'No items in cart', 200);
        }
        
        $formattedCartItems = $cartItems->map(function ($item) {
            // Handle potential null product
            if (!$item->product) {
                return null; // Will be filtered out below
            }
            
            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'product' => [
                    'product_id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => (float)$item->product->price, // Ensure float type
                    'stock' => (int)$item->product->stock,   // Ensure integer type
                    'status' => (bool)$item->product->status, // Ensure boolean type
                    'image' => $item->product->details && !empty($item->product->details->images) 
                        ? $item->product->details->images[0] 
                        : null,
                ],
            ];
        })->filter()->values(); // Filter out null items and reindex array
        
        return $this->success([
            'cart_items' => $formattedCartItems,
        ], 'Success', 200);
    }
    
    /**
     * Get cart total items count and price summary
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with cart summary or error
     */
    public function getCartSummary($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $cartItems = CartItem::where('user_id', $user->id)
            ->with('product')
            ->get();
        
        $totalItems = $cartItems->sum('quantity');
        $subtotal = $cartItems->sum(function($item) {
            return $item->quantity * ($item->product ? $item->product->price : 0);
        });
        
        return $this->success([
            'total_items' => $totalItems,
            'subtotal' => $subtotal,
            'item_count' => $cartItems->count()
        ], 'Success', 200);
    }
}