<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\UserCredential;

class CartItemPolicy
{
    /**
     * Determine whether the user can view any cart items.
     */
    public function viewAny(UserCredential $user): bool
    {
        // Only admin and staff can view all cart items
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view a specific cart item.
     */
    public function view(UserCredential $user, CartItem $cartItem): bool
    {
        // Users can only view their own cart items
        // Admins and staff can view any cart item
        return $user->id === $cartItem->user_id || $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create or update cart items.
     */
    public function update(UserCredential $user, CartItem $cartItem = null): bool
    {
        // If cartItem is null (creating new), check if user is authenticated
        if (!$cartItem) {
            return true; // Any authenticated user can create cart items
        }
        
        // Users can update only their own cart items
        return $user->id === $cartItem->user_id;
    }

    /**
     * Determine whether the user can delete a cart item.
     */
    public function delete(UserCredential $user, CartItem $cartItem): bool
    {
        // Users can only delete their own cart items
        // Admin can delete any cart item
        return $user->id === $cartItem->user_id || $user->isAdmin();
    }
    
    /**
     * Determine whether the user can clear their cart.
     */
    public function clear(UserCredential $user): bool
    {
        // Users can clear their own cart
        // No special permissions needed since we filter by user_id
        return true;
    }
}