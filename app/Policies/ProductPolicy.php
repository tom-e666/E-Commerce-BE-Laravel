<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\UserCredential;

class ProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(UserCredential $user): bool
    {
        // Everyone can view products
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(UserCredential $user, Product $product): bool
    {
        // Everyone can view products
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(UserCredential $user): bool
    {
        // Only admin and staff can create products
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(UserCredential $user, Product $product): bool
    {
        // Only admin and staff can update products
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(UserCredential $user, Product $product): bool
    {
        // Only admin can delete products
        return $user->isAdmin();
    }
}