<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\UserCredential;

class BrandPolicy
{
    /**
     * Determine whether the user can view any brands.
     */
    public function viewAny(?UserCredential $user): bool
    {
        // Anyone can view brands (public endpoint)
        return true;
    }

    /**
     * Determine whether the user can view the brand.
     */
    public function view(?UserCredential $user, Brand $brand): bool
    {
        // Anyone can view a brand (public endpoint)
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(UserCredential $user): bool
    {
        // Only admin and staff can create brands
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(UserCredential $user, Brand $brand): bool
    {
        // Only admin and staff can update brands
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(UserCredential $user, Brand $brand): bool
    {
        // Only admin can delete brands
        return $user->isAdmin();
    }
}