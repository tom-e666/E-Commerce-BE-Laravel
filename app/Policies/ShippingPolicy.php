<?php

namespace App\Policies;

use App\Models\Shipping;
use App\Models\UserCredential;
use Illuminate\Auth\Access\Response;

class ShippingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(UserCredential $userCredential): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(UserCredential $userCredential, Shipping $shipping): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(UserCredential $userCredential): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(UserCredential $userCredential, Shipping $shipping): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(UserCredential $userCredential, Shipping $shipping): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(UserCredential $userCredential, Shipping $shipping): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(UserCredential $userCredential, Shipping $shipping): bool
    {
        return false;
    }
}
