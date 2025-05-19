<?php
namespace App\Policies;

use App\Models\Order;
use App\Models\UserCredential;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(UserCredential $user): bool
    {
        // Only admin and staff can view all orders
        return $user->isAdmin() || $user->isStaff();
    }
    
    /**
     * Determine whether the user can view a specific order.
     */
    public function view(UserCredential $user, Order $order): bool
    {
        // Users can view their own orders, admins and staff can view any order
        return $user->id === $order->user_id || $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update an order's basic information.
     */
    public function update(UserCredential $user, Order $order): bool
    {
        // Only admin and staff can update orders
        return $user->isAdmin() || $user->isStaff();
    }
    
    /**
     * Determine whether the user can cancel an order.
     */
    public function cancel(UserCredential $user, Order $order): bool
    {
        // Users can only cancel their own pending orders
        // Admin and staff can cancel any order that's not delivered
        if ($user->isAdmin() || $user->isStaff()) {
            return $order->status !== 'delivered';
        }
        
        return $order->user_id === $user->id && $order->status === 'pending';
    }
    
    /**
     * Determine if user can change order status to confirmed
     */
    public function confirm(UserCredential $user, Order $order): bool
    {
        // Only admin and staff can confirm orders
        return ($user->isAdmin() || $user->isStaff()) && $order->status === 'pending';
    }
    
    /**
     * Determine if user can change order status to shipped
     */
    public function ship(UserCredential $user, Order $order): bool
    {
        // Only admin and staff can ship orders
        return ($user->isAdmin() || $user->isStaff()) && $order->status === 'confirmed';
    }
    
    /**
     * Determine if user can change order status to delivered
     */
    public function deliver(UserCredential $user, Order $order): bool
    {
        // Only admin and staff can mark orders as delivered
        return ($user->isAdmin() || $user->isStaff()) && $order->status === 'shipped';
    }
}