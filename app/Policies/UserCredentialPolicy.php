<?php

namespace App\Policies;

use App\Models\UserCredential;

class UserCredentialPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(UserCredential $user): bool
    {
        // Only admin and staff can view all users
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(UserCredential $user, UserCredential $targetUser): bool
    {
        // Users can view their own profile, admins and staff can view any profile
        return $user->id === $targetUser->id || $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(UserCredential $user, UserCredential $targetUser): bool
    {
        // Users can update their own profile, admins can update any profile
        return $user->id === $targetUser->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the role.
     */
    public function updateRole(UserCredential $user): bool
    {
        // Only admins can change roles
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(UserCredential $user, UserCredential $targetUser): bool
    {
        // Only admins can delete users and they cannot delete themselves
        return $user->isAdmin() && $user->id !== $targetUser->id;
    }

    /**
     * Determine whether the user can change their own password.
     */
    public function changePassword(UserCredential $user, UserCredential $targetUser): bool
    {
        // Users can only change their own password
        // Admins can change anyone's password
        return $user->id === $targetUser->id || $user->isAdmin();
    }
}