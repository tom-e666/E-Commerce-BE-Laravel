<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\CartItem;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
        if (isset($args['user_id']) && $args['user_id'] !== $user->id) {

            if (Gate::denies('viewAny', CartItem::class)) {
                return $this->error('You are not authorized to view other users\' cart items', 403);
            }
            $userId = $args['user_id'];            
        } else {
            $userId = $user->id;
        }
        $cartItems = $user->cart_items;
        if ($cartItems->isEmpty()) {
            return $this->success([
                'cart_items' => [], // Return empty array instead of null for consistency
            ], 'No items in cart', 200);
        }
        foreach($cartItems as $item) {
            $item->product= $item->product()->with('details')->first();
        }
        // $cartItems = $cartItems->where('user_id', $userId)
        //     ->select('id', 'product_id', 'quantity', 'updated_at')
        //     ->with(['product' => function($query) {
        //         $query->with('details');
        //     }])
        //     ->orderBy('updated_at', 'desc')
        //     ->get();
        // Filter out items the user doesn't have permission to view
        if ($cartItems->isEmpty()) {
            return $this->success([
                'cart_items' => [], // Return empty array instead of null for consistency
            ], 'No items in cart', 200);
        }
        Log::info('Cart items retrieved successfully', [
            'user_id' => $userId,
            'cart_items' => $cartItems->toArray(),
        ]);
        $formattedCartItems = $cartItems->map(function ($item) {
            // Handle potential null product
            if (!$item->product) {
                return null; // Will be filtered out below
            }
            $item->product->details = $item->product->details ?? null;
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
        
        // Check if user is getting summary for their own cart or has admin/staff privileges
        if (isset($args['user_id']) && $args['user_id'] !== $user->id) {
            // If user is trying to view someone else's cart summary, check viewAny policy
            if (Gate::denies('viewAny', CartItem::class)) {
                return $this->error('You are not authorized to view other users\' cart summary', 403);
            }
            $userId = $args['user_id'];
        } else {
            $userId = $user->id;
        }
        
        $cartItems = CartItem::where('user_id', $userId)
            ->with('product')
            ->get();
        
        // Filter out items the user doesn't have permission to view
        $cartItems = $cartItems->filter(function($item) {
            return Gate::allows('view', $item);
        });
        
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
    
    /**
     * Get a specific cart item
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with cart item or error
     */
    public function getCartItem($_, array $args)
    {
        if (!isset($args['cart_item_id'])) {
            return $this->error('cart_item_id is required', 400);
        }
        
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $cartItem = CartItem::with(['product' => function($query) {
                $query->with('details');
            }])
            ->find($args['cart_item_id']);
        
        if (!$cartItem) {
            return $this->error('Cart item not found', 404);
        }
        
        // Check if user is authorized to view this cart item
        if (Gate::denies('view', $cartItem)) {
            return $this->error('You are not authorized to view this cart item', 403);
        }
        
        if (!$cartItem->product) {
            return $this->error('Associated product not found', 404);
        }
        
        $formattedCartItem = [
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'product' => [
                'product_id' => $cartItem->product->id,
                'name' => $cartItem->product->name,
                'price' => (float)$cartItem->product->price,
                'stock' => (int)$cartItem->product->stock,
                'status' => (bool)$cartItem->product->status,
                'image' => $cartItem->product->details && !empty($cartItem->product->details->images) 
                    ? $cartItem->product->details->images[0] 
                    : null,
            ],
        ];
        
        return $this->success([
            'cart_item' => $formattedCartItem,
        ], 'Success', 200);
    }
}