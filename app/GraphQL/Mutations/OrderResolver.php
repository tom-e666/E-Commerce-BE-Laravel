<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AuthService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\GraphQL\Traits\GraphQLResponse;

final class OrderResolver
{
    use GraphQLResponse;
    
    /**
     * Get a specific order by ID
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with order data or error
     */
    public function getOrder($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        if (!isset($args['order_id'])) {
            return $this->error('order_id is required', 400);
        }
        
        $order = Order::find($args['order_id']);
        if ($order === null) {
            return $this->error('Order not found', 404);
        }
        
        // Check if user can view this order using policy
        if (Gate::denies('view', $order)) {
            return $this->error('You are not authorized to view this order', 403);
        }
        
        return $this->success([
            'order' => $order->load('items.product'),
        ], 'Success', 200);
    }
    
    /**
     * Get orders for a specific user (admin only)
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with orders data or error
     */
    public function getOrdersFromUser($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Only admin or staff can view other users' orders
        if (!$user->isAdmin() && !$user->isStaff()) {
            return $this->error('Unauthorized', 403);
        }
        
        if (!isset($args['user_id'])) {
            return $this->error('user_id is required', 400);
        }
        
        $orders = Order::where('user_id', $args['user_id'])->get();
        
        return $this->success([
            'orders' => $orders->load('items.product'),
        ], 'Success', 200);
    }
    
    /**
     * Get orders for the authenticated user
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with orders data or error
     */
    public function getUserOrders($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $orders = Order::where('user_id', $user->id)->get();
        
        return $this->success([
            'orders' => $orders->load('items.product'),
        ], 'Success', 200);
    }
    
    /**
     * Get all orders with filtering options (admin/staff only)
     * 
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with orders data or error
     */
    public function getAllOrders($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check if user can view all orders using policy
        if (Gate::denies('viewAny', Order::class)) {
            return $this->error('You are not authorized to view all orders', 403);
        }
        
        $query = Order::query();
        
        // Apply filters
        if (isset($args['user_id'])) {
            $query->where('user_id', $args['user_id']);
        }
        
        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }
        
        if (isset($args['created_after'])) {
            $query->where('created_at', '>=', $args['created_after']);
        }
        
        if (isset($args['created_before'])) {
            $query->where('created_at', '<=', $args['created_before']);
        }
        
        $orders = $query->get();
        
        return $this->success([
            'orders' => $orders->load('items.product'),
        ], 'Success', 200);
    }
}