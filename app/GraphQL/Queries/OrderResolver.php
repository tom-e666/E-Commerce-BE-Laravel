<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AuthService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\GraphQL\Traits\GraphQLResponse;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
final readonly class OrderResolver
{
    use GraphQLResponse;
    
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    
    /**
     * Get a specific order by ID
     */
    public function getOrder($_, array $args): array
    {
        if (!isset($args['order_id'])) {
            return $this->error('order_id is required', 400);
        }
        
        $order = Order::find($args['order_id']);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }
        
        // Check if current user can view this order
        $user = AuthService::Auth();
        if (!$user || Gate::denies('view', $order)) {
            return $this->error('You are not authorized to view this order', 403);
        }
        
        // Load order items and products
        $order->load(['items.product', 'user', 'payment']);
        
        // Get all product IDs from this order
        $productIds = $order->items->pluck('product_id')->toArray();
        
        // Load all MongoDB product details in a single query
        
        $productDetails = ProductDetail::whereIn('product_id', $productIds)->get()->keyBy('product_id');
        Log::info('Product details loaded from MongoDB', ['product_details' => $productDetails]);
        // Format the response
        $formattedOrder = [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'total_price' => (float)$order->total_price,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'items' => $order->items->map(function($item) use ($productDetails) {
                // Find product details from MongoDB
                $details = $productDetails->get((string)$item->product_id);
                
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product ? $item->product->name : 'Unknown Product',
                    'price' => (float)$item->price,
                    'quantity' => $item->quantity,
                    'image' => $details && !empty($details->images) ? $details->images[0] : null,
                ];
            })
        ];
        
        return $this->success([
            'order' => $formattedOrder
        ], 'Success', 200);
    }
    
    /**
     * Get orders for a specific user (admin/staff function)
     */
    public function getOrdersFromUser($_, array $args): array
    {
        if (!isset($args['user_id'])) {
            return $this->error('user_id is required', 400);
        }
        
        // Check if current user can view other users' orders
        $user = AuthService::Auth();
        if (Gate::denies('viewAny', Order::class)) {
            return $this->error('You are not authorized to view orders from other users', 403);
        }
        
        // Apply pagination if provided
        $page = $args['page'] ?? 1;
        $perPage = $args['per_page'] ?? 10;
        
        $query = Order::where('user_id', $args['user_id']);
        
        // Apply filters if provided
        if (isset($args['status']) && !empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        
        if (isset($args['date_from'])) {
            $query->where('created_at', '>=', $args['date_from']);
        }
        
        if (isset($args['date_to'])) {
            $query->where('created_at', '<=', $args['date_to']);
        }
        
        // Get paginated results
        $orders = $query->orderBy('created_at', 'desc')
                        ->paginate($perPage, ['*'], 'page', $page);
        
        if ($orders->isEmpty()) {
            return $this->success([
                'orders' => [],
                'pagination' => [
                    'total' => 0,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'last_page' => 1
                ]
            ], 'No orders found for this user', 200);
        }
        
        return $this->success([
            'orders' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage()
            ]
        ], 'Success', 200);
    }
    
    /**
     * Get orders for the currently authenticated user
     */
    public function getUserOrders($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
    
        $query = Order::where('user_id', $user->id);
        
        // Apply filters if provided
        if (isset($args['status']) && !empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        
        if (isset($args['date_from'])) {
            $query->where('created_at', '>=', $args['date_from']);
        }
        
        if (isset($args['date_to'])) {
            $query->where('created_at', '<=', $args['date_to']);
        }
        
        // Get paginated results
        $orders = $query->orderBy('created_at', 'desc');
        
        
        $orders = $query->with(['items.product', 'user', 'payment'])->get();
        
        // Format orders for response
        $formattedOrders = $orders->map(function ($order) {
            return $this->formatOrderResponse($order);
        });
        if ($orders===[]) {
            return $this->success([
                'orders' => [],
            ], 'You have no orders', 200);
        }
        return $this->success([
            'orders' => $formattedOrders,
        ], 'Success', 200);
    }

    /**
     * Get all orders (admin/staff function)
     */
    public function getAllOrders($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        // Check if user can view all orders
        if (Gate::denies('viewAny', Order::class)) {
            return $this->error('You are not authorized to view all orders', 403);
        }
        
        $query = Order::query();
        
        // Apply filters if provided
        if (isset($args['status']) && !empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        
        if (isset($args['user_id']) && !empty($args['user_id'])) {
            $query->where('user_id', $args['user_id']);
        }
        
        if (isset($args['created_after'])) {
            $query->where('created_at', '>=', $args['created_after']);
        }
        
        if (isset($args['created_before'])) {
            $query->where('created_at', '<=', $args['created_before']);
        }
        
        // Get all orders without pagination to match schema
        $orders = $query->with(['items.product', 'user', 'payment'])->get();
        
        // Format orders for response
        $formattedOrders = $orders->map(function ($order) {
            return $this->formatOrderResponse($order);
        });
        
        return $this->success([
            'orders' => $formattedOrders,
        ], 'Success', 200);
    }
    
    /**
     * Get order statistics (for admin dashboard)
     */
    public function getOrderStats($_, array $args): array
    {
        $user = AuthService::Auth();
        
        // Check if user has permission to view statistics
        if (Gate::denies('viewAny', Order::class)) {
            return $this->error('You are not authorized to view order statistics', 403);
        }
        
        // Get date range (last 30 days by default)
        $endDate = now();
        $startDate = now()->subDays(30);
        
        if (isset($args['date_from'])) {
            $startDate = $args['date_from'];
        }
        
        if (isset($args['date_to'])) {
            $endDate = $args['date_to'];
        }
        
        // Get total orders count
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Get orders by status
        $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        // Get total revenue
        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');
        
        return $this->success([
            'total_orders' => $totalOrders,
            'orders_by_status' => $ordersByStatus,
            'total_revenue' => $totalRevenue,
            'date_range' => [
                'from' => $startDate,
                'to' => $endDate
            ]
        ], 'Success', 200);
    }
    
    /**
     * Format order data for response
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderResponse(Order $order): array
    {
        // Ensure product details are loaded if needed
        if (!$order->relationLoaded('items.product')) {
            $order->load('items.product');
        }
        return [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'total_price' => (float)$order->total_price,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'items' => $order->items->map(function($item){
                // Find product details from MongoDB
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product ? $item->product->name : 'Unknown Product',
                    'price' => (float)$item->price,
                    'quantity' => $item->quantity,
                    'image' => $item->product->image(),
                ];
            })
        ];
    }

    /**
     * Get paginated orders for admin/staff with filters and sorting
     */
    public function getPaginatedOrders($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user || Gate::denies('viewAny', Order::class)) {
            return $this->error('You are not authorized to view orders', 403);
        }

        try {
            $query = Order::query();

            // Apply filters
            if (isset($args['user_id']) && !empty($args['user_id'])) {
                $query->where('user_id', $args['user_id']);
            }

            if (isset($args['status']) && !empty($args['status'])) {
                $query->where('status', $args['status']);
            }

            if (isset($args['created_after'])) {
                $createdAfter = Carbon::parse($args['created_after'])->startOfDay();
                $query->where('created_at', '>=', $createdAfter);
            }

            if (isset($args['created_before'])) {
                $createdBefore = Carbon::parse($args['created_before'])->endOfDay();
                $query->where('created_at', '<=', $createdBefore);
            }

            // Apply search if provided (search in order ID or user info)
            if (isset($args['search']) && !empty($args['search'])) {
                $searchTerm = $args['search'];
                $query->where(function($q) use ($searchTerm) {
                    $q->where('id', 'like', "%{$searchTerm}%")
                      ->orWhereHas('user', function($q) use ($searchTerm) {
                          $q->where('full_name', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                      });
                });
            }

            // Apply sorting
            $sortField = $args['sort_field'] ?? 'created_at';
            $sortDirection = $args['sort_direction'] ?? 'desc';

            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['id', 'user_id', 'status', 'total_price', 'created_at', 'updated_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }

            // Validate sort direction
            $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortDirection);

            // Apply pagination
            $page = $args['page'] ?? 1;
            $perPage = $args['per_page'] ?? 10;

            // Eager load relationships to avoid N+1 queries
            $orders = $query->with(['items.product', 'user', 'payment'])
                           ->paginate($perPage, ['*'], 'page', $page);

            // Format orders for response
            $formattedOrders = collect($orders->items())->map(function ($order) {
                return $this->formatOrderResponse($order);
            });

            return $this->success([
                'orders' => $formattedOrders,
                'pagination' => [
                    'total' => $orders->total(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                    'has_more_pages' => $orders->hasMorePages(),
                ]
            ], 'Success', 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch paginated orders: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'args' => $args
            ]);
            return $this->error('Failed to fetch orders: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get paginated orders for current user
     */
    public function getPaginatedUserOrders($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        try {
            $query = Order::where('user_id', $user->id);

            // Apply filters
            if (isset($args['status']) && !empty($args['status'])) {
                $query->where('status', $args['status']);
            }

            if (isset($args['created_after'])) {
                $query->where('created_at', '>=', $args['created_after']);
            }

            if (isset($args['created_before'])) {
                $query->where('created_at', '<=', $args['created_before']);
            }

            // Apply sorting
            $sortField = $args['sort_field'] ?? 'created_at';
            $sortDirection = $args['sort_direction'] ?? 'desc';

            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['id', 'status', 'total_price', 'created_at', 'updated_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }

            // Validate sort direction
            $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortDirection);

            // Apply pagination
            $page = $args['page'] ?? 1;
            $perPage = $args['per_page'] ?? 10;

            // Eager load relationships to avoid N+1 queries
            $orders = $query->with(['items.product', 'payment'])
                           ->paginate($perPage, ['*'], 'page', $page);

            // Format orders for response
            $formattedOrders = collect($orders->items())->map(function ($order) {
                return $this->formatOrderResponse($order);
            });

            return $this->success([
                'orders' => $formattedOrders,
                'pagination' => [
                    'total' => $orders->total(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                    'has_more_pages' => $orders->hasMorePages(),
                ]
            ], 'Success', 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch paginated user orders: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'args' => $args,
                'user_id' => $user->id
            ]);
            return $this->error('Failed to fetch orders: ' . $e->getMessage(), 500);
        }
    }

    public function getStatusOrder($_, array $args): array
    {
        try {
            $user = auth('api')->user();

            if (!isset($args['order_id'])) {
                return $this->error('order_id is required', 400);
            }

            \Log::info('Order ID:', ['order_id' => $args['order_id']]);

            $order = Order::with(['items.product', 'user', 'payment'])
                ->find((int)$args['order_id']);

            if (!$order) {
                return $this->error('Order not found', 404);
            }

            \Log::info('Order retrieved', [
                'user_id' => $user ? $user->id : null,
                'order_id' => $order->id,
            ]);

            $formattedOrder = [
                'id' => $order->id,
                'status' => $order->status,
                'total_price' => (float)$order->total_price,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'items' => $order->items->map(function($item){
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->product ? $item->product->name : 'Unknown Product',
                        'price' => (float)$item->price,
                        'quantity' => $item->quantity,
                        'image' => null,
                    ];
                })
            ];

            return $this->success([
                'order' => $formattedOrder,
            ], 'Success', 200);
        } catch (\Throwable $e) {
            \Log::error('Error in getStatusOrder', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'args' => $args,
            ]);
            return $this->error('Internal server error: ' . $e->getMessage(), 500);
        }
    }
    public function getOrderByTransaction($_, array $args): array
    {
        try {
            $user = auth('api')->user();

            if (!isset($args['transaction_id'])) {
                return $this->error('transaction_id is required', 400);
            }

            \Log::info('Transaction ID:', ['transaction_id' => $args['transaction_id']]);

            $payment = \App\Models\Payment::where('transaction_id', $args['transaction_id'])->first();

            if (!$payment) {
                return $this->error('Payment not found', 404);
            }

            $order = Order::with(['items.product', 'user', 'payment'])
                ->find($payment->order_id);

            if (!$order) {
                return $this->error('Order not found', 404);
            }

            \Log::info('Order retrieved by transaction', [
                'user_id' => $user ? $user->id : null,
                'order_id' => $order->id,
                'transaction_id' => $args['transaction_id'],
            ]);

            $formattedOrder = [
                'id' => $order->id,
                'status' => $order->status,
                'total_price' => (float)$order->total_price,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'items' => $order->items->map(function($item){
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->product ? $item->product->name : 'Unknown Product',
                        'price' => (float)$item->price,
                        'quantity' => $item->quantity,
                        'image' => null,
                    ];
                })
            ];

            return $this->success([
                'order' => $formattedOrder,
            ], 'Success', 200);
        } catch (\Throwable $e) {
            \Log::error('Error in getStatusOrderByTransaction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'args' => $args,
            ]);
            return $this->error('Internal server error: ' . $e->getMessage(), 500);
        }
    }
}