<?php

namespace App\Policies;

use App\Models\UserCredential;
use Illuminate\Auth\Access\HandlesAuthorization;

class MetricsPolicy
{
    /**
     * Determine whether the user can view dashboard metrics
     */
    public function viewDashboard(UserCredential $user): bool
    {
        // Only admin can view dashboard metrics
        return $user->isAdmin();
    }
    
    /**
     * Determine whether the user can view sales metrics
     */
    public function viewSalesMetrics(UserCredential $user): bool
    {
        // Admin can view sales metrics
        // Staff can be allowed if needed
        return $user->isAdmin();
    }
    
    /**
     * Determine whether the user can view product metrics
     */
    public function viewProductMetrics(UserCredential $user): bool
    {
        // Admin can view product metrics
        // Staff can also view for inventory management purposes
        return $user->isAdmin() || $user->isStaff();
    }
    
    /**
     * Determine whether the user can view support metrics
     */
    public function viewSupportMetrics(UserCredential $user): bool
    {
        // Admin and staff can view support metrics
        return $user->isAdmin() || $user->isStaff();
    }
}