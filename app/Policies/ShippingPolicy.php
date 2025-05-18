<?php
<?php

namespace App\Policies;

use App\Models\Shipping;
use App\Models\UserCredential;
use App\Models\Order;

class ShippingPolicy
{
    /**
     * Determine whether the user can view any shipping records.
     */
    public function viewAny(UserCredential $user): bool
    {
        // Admin and staff can view all shipping records
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view the shipping record.
     */
    public function view(UserCredential $user, Shipping $shipping): bool
    {
        // Users can view shipping for their own orders
        // Admin and staff can view any shipping
        $order = Order::find($shipping->order_id);
        return ($order && $order->user_id === $user->id) || 
               $user->isAdmin() || 
               $user->isStaff();
    }

    /**
     * Determine whether the user can create shipping.
     */
    public function create(UserCredential $user, Order $order): bool
    {
        // Users can create shipping for their own orders
        // Admin and staff can create shipping for any order
        return $order->user_id === $user->id || 
               $user->isAdmin() || 
               $user->isStaff();
    }

    /**
     * Determine whether the user can cancel the shipping.
     */
    public function cancel(UserCredential $user, Shipping $shipping): bool
    {
        // Users can cancel shipping for their own orders if it's pending
        // Admin and staff can cancel any shipping that's not delivered
        $order = Order::find($shipping->order_id);
        
        if ($user->isAdmin() || $user->isStaff()) {
            return $shipping->status !== 'delivered';
        }
        
        return $order && 
               $order->user_id === $user->id && 
               $shipping->status === 'pending';
    }

    /**
     * Determine whether the user can update the shipping.
     */
    public function update(UserCredential $user, Shipping $shipping): bool
    {
        // Only admin and staff can update shipping status
        return $user->isAdmin() || $user->isStaff();
    }
}