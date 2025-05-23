<?php

namespace App\GraphQL\Queries;

use App\Models\Order;
use App\Models\Product;
use App\Models\UserCredential;
use App\Models\SupportTicket;
use App\Models\OrderItem;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class MetricsResolver
{
    use GraphQLResponse;

    /**
     * Get high-level metrics for admin dashboard
     */
    public function getDashboardMetrics($_, array $args)
    {
        $user = AuthService::Auth();
        
        Log::debug('Metrics auth check', [
            'user_id' => $user ? $user->id : null,
            'user_role' => $user ? $user->role : null,
            'is_admin' => $user ? $user->isAdmin() : false
        ]);
        
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check authorization using policy
        if (Gate::denies('viewDashboard', 'App\Models\Metrics')) {
            Log::warning('Metrics policy check failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'policy' => 'viewDashboard'
            ]);
            
            return $this->error('You are not authorized to view dashboard metrics', 403);
        }
        
        // Cache key based on date to refresh daily
        $cacheKey = 'dashboard_metrics_' . date('Y-m-d');
        $cacheDuration = 60; // 1 hour in minutes
        
        // Return cached metrics if available
        return Cache::remember($cacheKey, $cacheDuration, function() {
            $today = Carbon::today();
            $startOfWeek = Carbon::now()->startOfWeek();
            $startOfMonth = Carbon::now()->startOfMonth();
    
            // Order metrics
            $ordersToday = Order::whereDate('created_at', $today)->count();
            $ordersWeek = Order::where('created_at', '>=', $startOfWeek)->count();
            $ordersMonth = Order::where('created_at', '>=', $startOfMonth)->count();
            
            // Revenue metrics
            $revenueToday = Order::whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('total_price');
            
            $revenueWeek = Order::where('created_at', '>=', $startOfWeek)
                ->where('status', 'completed')
                ->sum('total_price');
                
            $revenueMonth = Order::where('created_at', '>=', $startOfMonth)
                ->where('status', 'completed')
                ->sum('total_price');
            
            // Product metrics
            $totalProducts = Product::count();
            $lowStockProducts = Product::where('stock', '<=', 10)
                ->where('stock', '>', 0)
                ->where('status', true)
                ->count();
            $outOfStockProducts = Product::where('stock', 0)
                ->where('status', true)
                ->count();
            
            // User metrics
            $totalUsers = UserCredential::where('role', 'user')->count();
            $newUsersToday = UserCredential::where('role', 'user')
                ->whereDate('created_at', $today)
                ->count();
            $newUsersWeek = UserCredential::where('role', 'user')
                ->where('created_at', '>=', $startOfWeek)
                ->count();
            
            // Support metrics
            $supportTicketsOpen = SupportTicket::where('status', SupportTicket::STATUS_OPEN)
                ->orWhere('status', SupportTicket::STATUS_IN_PROGRESS)
                ->count();
            $supportTicketsTotal = SupportTicket::count();
            
            return $this->success([
                'orders_today' => $ordersToday,
                'orders_week' => $ordersWeek,
                'orders_month' => $ordersMonth,
                'revenue_today' => (float) $revenueToday,
                'revenue_week' => (float) $revenueWeek,
                'revenue_month' => (float) $revenueMonth,
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'total_users' => $totalUsers,
                'new_users_today' => $newUsersToday,
                'new_users_week' => $newUsersWeek,
                'support_tickets_open' => $supportTicketsOpen,
                'support_tickets_total' => $supportTicketsTotal,
            ], 'Dashboard metrics retrieved successfully', 200);
        });
    }

    /**
     * Get detailed sales metrics with specified timeframe
     */
    public function getSalesMetrics($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check authorization using policy
        if (Gate::denies('viewSalesMetrics', 'App\Models\Metrics')) {
            return $this->error('You are not authorized to view sales metrics', 403);
        }
        
        $timeframe = $args['timeframe'] ?? 'week'; // Default to weekly
        $startDate = isset($args['start_date']) ? Carbon::parse($args['start_date']) : null;
        $endDate = isset($args['end_date']) ? Carbon::parse($args['end_date']) : Carbon::now();
        
        // Validate custom date range if provided
        if ($timeframe === 'custom' && (!$startDate || !$endDate)) {
            return $this->error('Start and end dates are required for custom timeframe', 400);
        }
        
        // Set default start date based on timeframe if not custom
        if (!$startDate) {
            switch ($timeframe) {
                case 'day':
                    $startDate = Carbon::now()->subDay();
                    break;
                case 'week':
                    $startDate = Carbon::now()->subWeek();
                    break;
                case 'month':
                    $startDate = Carbon::now()->subMonth();
                    break;
                case 'year':
                    $startDate = Carbon::now()->subYear();
                    break;
                default:
                    $startDate = Carbon::now()->subWeek();
            }
        }
        
        // Cache key based on parameters
        $cacheKey = "sales_metrics_{$timeframe}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        $cacheDuration = 30; // 30 minutes
        
        return Cache::remember($cacheKey, $cacheDuration, function() use ($startDate, $endDate) {
            // Get daily metrics
            $dailyMetrics = $this->getSalesByDateGroup($startDate, $endDate, 'day');
            
            // Get weekly metrics
            $weeklyMetrics = $this->getSalesByDateGroup($startDate, $endDate, 'week');
            
            // Get monthly metrics
            $monthlyMetrics = $this->getSalesByDateGroup($startDate, $endDate, 'month');
            
            return $this->success([
                'daily_metrics' => $dailyMetrics,
                'weekly_metrics' => $weeklyMetrics,
                'monthly_metrics' => $monthlyMetrics,
            ], 'Sales metrics retrieved successfully', 200);
        });
    }
    
    /**
     * Get product-related metrics
     */
    public function getProductMetrics($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check authorization using policy
        if (Gate::denies('viewProductMetrics', 'App\Models\Metrics')) {
            return $this->error('You are not authorized to view product metrics', 403);
        }
        
        $limit = $args['limit'] ?? 10;
        
        // Cache key based on limit
        $cacheKey = "product_metrics_limit_{$limit}_" . date('Y-m-d');
        $cacheDuration = 60; // 1 hour
        
        return Cache::remember($cacheKey, $cacheDuration, function() use ($limit) {
            // Get top selling products
            $topSellingProducts = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(order_items.quantity) as sales_count'),
                    DB::raw('SUM(order_items.quantity * order_items.price) as revenue'),
                    'products.stock as stock_remaining'
                )
                ->where('orders.status', 'completed')
                ->groupBy('products.id', 'products.name', 'products.stock')
                ->orderBy('sales_count', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($item) {
                    // Calculate stock percentage
                    $originalStock = $item->sales_count + $item->stock_remaining;
                    $stockPercentage = $originalStock > 0 
                        ? round(($item->stock_remaining / $originalStock) * 100, 2) 
                        : 0;
                    
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'sales_count' => $item->sales_count,
                        'revenue' => (float) $item->revenue,
                        'stock_remaining' => $item->stock_remaining,
                        'stock_percentage' => $stockPercentage
                    ];
                });
            
            // Get low stock products
            $lowStockProducts = Product::where('stock', '<=', 10)
                ->where('stock', '>', 0)
                ->where('status', true)
                ->select('id', 'name', 'stock as stock_remaining')
                ->orderBy('stock', 'asc')
                ->limit($limit)
                ->get()
                ->map(function($product) {
                    $salesCount = OrderItem::where('product_id', $product->id)->sum('quantity');
                    
                    // Calculate stock percentage
                    $originalStock = $salesCount + $product->stock_remaining;
                    $stockPercentage = $originalStock > 0 
                        ? round(($product->stock_remaining / $originalStock) * 100, 2) 
                        : 0;
                    
                    // Calculate revenue
                    $revenue = OrderItem::where('product_id', $product->id)
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.status', 'completed')
                        ->sum(DB::raw('order_items.quantity * order_items.price'));
                    
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sales_count' => $salesCount,
                        'revenue' => (float) $revenue,
                        'stock_remaining' => $product->stock_remaining,
                        'stock_percentage' => $stockPercentage
                    ];
                });
                
            return $this->success([
                'top_selling_products' => $topSellingProducts,
                'low_stock_products' => $lowStockProducts
            ], 'Product metrics retrieved successfully', 200);
        });
    }
    
    /**
     * Get support ticket metrics
     */
    public function getSupportMetrics($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check authorization using policy
        if (Gate::denies('viewSupportMetrics', 'App\Models\Metrics')) {
            return $this->error('You are not authorized to view support metrics', 403);
        }
        
        // Cache for a shorter period since ticket statuses change frequently
        $cacheKey = 'support_metrics_' . date('Y-m-d_H');
        $cacheDuration = 30; // 30 minutes
        
        return Cache::remember($cacheKey, $cacheDuration, function() {
            // Get ticket counts by status
            $openTickets = SupportTicket::where('status', SupportTicket::STATUS_OPEN)->count();
            $inProgressTickets = SupportTicket::where('status', SupportTicket::STATUS_IN_PROGRESS)->count();
            $resolvedTickets = SupportTicket::where('status', SupportTicket::STATUS_RESOLVED)->count();
            
            // Calculate average resolution time (for closed tickets)
            $resolvedTicketsWithTime = SupportTicket::where('status', SupportTicket::STATUS_RESOLVED)
                ->orWhere('status', SupportTicket::STATUS_CLOSED)
                ->select(
                    DB::raw('TIMESTAMPDIFF(HOUR, created_at, updated_at) as resolution_time')
                )
                ->get();
            
            $averageResolutionTime = 0;
            if ($resolvedTicketsWithTime->count() > 0) {
                $averageResolutionTime = $resolvedTicketsWithTime->avg('resolution_time');
            }
            
            return $this->success([
                'open_tickets' => $openTickets,
                'in_progress_tickets' => $inProgressTickets,
                'resolved_tickets' => $resolvedTickets,
                'average_resolution_time' => (float) $averageResolutionTime
            ], 'Support metrics retrieved successfully', 200);
        });
    }
    
    /**
     * Helper to group sales data by different time periods
     */
    private function getSalesByDateGroup($startDate, $endDate, $groupBy = 'day')
    {
        $format = '%Y-%m-%d'; // Default daily format
        $dbFormat = 'Y-m-d';  // Default format for Carbon
        
        // Set format based on grouping
        if ($groupBy === 'week') {
            $format = '%Y-%u';  // ISO week format
            $dbFormat = 'Y-W';
        } elseif ($groupBy === 'month') {
            $format = '%Y-%m';
            $dbFormat = 'Y-m';
        }
        
        // Get orders grouped by date format
        $salesData = DB::table('orders')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$format') as date"),
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '$format')"))
            ->orderBy('date', 'asc')
            ->get();
        
        // Format the data into usable metrics
        $formattedMetrics = $salesData->map(function($item) {
            $averageOrderValue = $item->orders_count > 0 
                ? round($item->revenue / $item->orders_count, 2) 
                : 0;
                
            return [
                'date' => $item->date,
                'revenue' => (float) $item->revenue,
                'orders_count' => $item->orders_count,
                'average_order_value' => (float) $averageOrderValue
            ];
        });
        
        return $formattedMetrics;
    }
}