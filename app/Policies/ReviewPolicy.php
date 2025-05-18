<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\UserCredential;

class ReviewPolicy
{
    /**
     * Determine whether the user can view reviews.
     */
    public function viewAny(UserCredential $user): bool
    {
        // Admin and staff can view all reviews
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view a specific review.
     */
    public function view(UserCredential $user, Review $review): bool
    {
        // Users can view their own reviews, admins and staff can view any review
        return $user->id === $review->user_id || $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create reviews.
     */
    public function create(UserCredential $user): bool
    {
        // Any authenticated user can create reviews for products they've purchased
        return true;
    }

    /**
     * Determine whether the user can update the review.
     */
    public function update(UserCredential $user, Review $review): bool
    {
        // Users can update their own reviews, admins and staff can update any review
        return $user->id === $review->user_id || $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete the review.
     */
    public function delete(UserCredential $user, Review $review): bool
    {
        // Users can delete their own reviews, admins and staff can delete any review
        return $user->id === $review->user_id || $user->isAdmin() || $user->isStaff();
    }
}