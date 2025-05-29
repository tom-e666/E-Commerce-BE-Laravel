<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\CartItem;
use App\Models\ProductDetail;
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
        
        // FIXED: Use direct query for better control and to fix relationship loading
        $cartItems = CartItem::where('user_id', $userId)
            ->with('product') // Eager load product relationship
            ->get();
        // If no items, return empty array
        if ($cartItems->isEmpty()) {
            return $this->success([
                'cart_items' => [], 
            ], 'No items in cart', 200);
        }
        
        // Get all product IDs for batch MongoDB query
        $productIds = $cartItems->pluck('product_id')->filter()->toArray();
        
        // FIXED: Batch load product details from MongoDB
        $productDetails = ProductDetail::whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');
            
        
        // Format cart items with product details
        $formattedCartItems = $cartItems->map(function ($item) use ($productDetails) {
            // Skip items with missing products
            if (!$item->product) {
                Log::warning('Cart item has null product', ['cart_item_id' => $item->id]);
                return null;
            }
            
            // Get product details from our batch-loaded collection
            $details = $productDetails->get($item->product_id);
            
            // Debug if product details are missing
            if (!$details) {
                Log::warning('Product details not found', ['product_id' => $item->product_id]);
            }
            
            $image = null;
            if ($details && !empty($details->images)) {
                // Handle both array and JSON string formats
                if (is_string($details->images)) {
                    $imagesArray = json_decode($details->images, true);
                    $image = $imagesArray[0] ?? null;
                } else {
                    $image = $details->images[0] ?? null;
                }
            }
            
            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'product' => [
                    'product_id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => (float)$item->product->price,
                    'stock' => (int)$item->product->stock,
                    'status' => (bool)$item->product->status,
                    'image' => $image,
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
                    ? $cartItem->product->details->images[0] : null,
            ],
        ];
        
        return $this->success([
            'cart_item' => $formattedCartItem,
        ], 'Success', 200);
    }

    /**
     * Get paginated cart items with sorting
     *
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with paginated cart items or error
     */
    public function getPaginatedCartItems($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        // Check if user is getting cart items for their own cart or has admin/staff privileges
        if (isset($args['user_id']) && $args['user_id'] !== $user->id) {
            if (Gate::denies('viewAny', CartItem::class)) {
                return $this->error('You are not authorized to view other users\' cart items', 403);
            }
            $userId = $args['user_id'];
        } else {
            $userId = $user->id;
        }

        try {
            $query = CartItem::where('user_id', $userId)
                ->with('product'); // Eager load product relationship

            // Apply sorting
            $sortField = $args['sort_field'] ?? 'created_at';
            $sortDirection = $args['sort_direction'] ?? 'desc';

            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['created_at', 'updated_at', 'quantity'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }

            // Validate sort direction
            $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortDirection);

            // Apply pagination
            $page = $args['page'] ?? 1;
            $perPage = $args['per_page'] ?? 10;

            $cartItems = $query->paginate($perPage, ['*'], 'page', $page);

            // If no items, return empty array with pagination info
            if ($cartItems->isEmpty()) {
                return $this->success([
                    'cart_items' => [],
                    'pagination' => [
                        'total' => $cartItems->total(),
                        'current_page' => $cartItems->currentPage(),
                        'per_page' => $cartItems->perPage(),
                        'last_page' => $cartItems->lastPage(),
                        'from' => $cartItems->firstItem(),
                        'to' => $cartItems->lastItem(),
                        'has_more_pages' => $cartItems->hasMorePages(),
                    ]
                ], 'No items in cart', 200);
            }

            // Get all product IDs for batch MongoDB query
            $productIds = collect($cartItems->items())->pluck('product_id')->filter()->toArray();

            // Batch load product details from MongoDB
            $productDetails = ProductDetail::whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            // Format cart items with product details
            $formattedCartItems = collect($cartItems->items())->map(function ($item) use ($productDetails) {
                // Skip items without valid products
                if (!$item->product) {
                    return null;
                }

                // Check if user is authorized to view this cart item
                if (Gate::denies('view', $item)) {
                    return null;
                }

                $details = $productDetails->get($item->product_id);

                $image = null;
                if ($details && !empty($details->images)) {
                    // Handle both array and JSON string formats
                    if (is_string($details->images)) {
                        $imagesArray = json_decode($details->images, true);
                        $image = $imagesArray[0] ?? null;
                    } else {
                        $image = $details->images[0] ?? null;
                    }
                }

                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'product_id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => (float)$item->product->price,
                        'stock' => (int)$item->product->stock,
                        'status' => (bool)$item->product->status,
                        'image' => $image,
                    ],
                ];
            })->filter()->values(); // Filter out null items and reindex array

            return $this->success([
                'cart_items' => $formattedCartItems,
                'pagination' => [
                    'total' => $cartItems->total(),
                    'current_page' => $cartItems->currentPage(),
                    'per_page' => $cartItems->perPage(),
                    'last_page' => $cartItems->lastPage(),
                    'from' => $cartItems->firstItem(),
                    'to' => $cartItems->lastItem(),
                    'has_more_pages' => $cartItems->hasMorePages(),
                ]
            ], 'Success', 200);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch cart items: ' . $e->getMessage(), 500);
        }
    }
}