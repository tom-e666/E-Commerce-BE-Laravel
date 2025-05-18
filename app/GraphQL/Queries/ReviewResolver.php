<?php
<?php

namespace App\GraphQL\Queries;

use App\Models\Review;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;

class ReviewResolver
{
    use GraphQLResponse;

    /**
     * Get all reviews (admin/staff only)
     */
    public function getAllReviews($root, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        if (Gate::denies('viewAny', Review::class)) {
            return $this->error('Unauthorized', 403);
        }
        
        $query = Review::query();
        
        if (isset($args['user_id'])) {
            $query->where('user_id', $args['user_id']);
        }
        
        $reviews = $query->get();
        return $this->success([
            'reviews' => $reviews,
        ], 'Success', 200);
    }

    /**
     * Get a specific review
     */
    public function getReview($root, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $review = Review::find($args['id']);
        
        if (!$review) {
            return $this->error('Review not found', 404);
        }
        
        // Check if user can view this review
        if (Gate::denies('view', $review)) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success([
            'review' => $review->toArray(),
        ], 'Success', 200);
    }
    
    /**
     * Get reviews by the authenticated user
     */
    public function getReviewsByUser($root, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $reviews = Review::where('user_id', $user->id)->get();
        return $this->success([
            'reviews' => $reviews,
        ], 'Success', 200);
    }
    
    /**
     * Get reviews for a specific product (public endpoint)
     */
    public function getReviewsByProduct($root, array $args)
    {
        if (!isset($args['product_id'])) {
            return $this->error('product_id is required', 400);
        }
        
        $productId = (string)$args['product_id'];
        $query = Review::where('product_id', $productId)
                      ->orderBy('created_at', 'desc');
                      
        // Handle pagination
        if (isset($args['limit']) && $args['limit'] > 0) {
            $page = $args['page'] ?? 1;
            $query->skip(($page - 1) * $args['limit'])
                  ->take($args['limit']);
        }
        
        $reviews = $query->get();
        
        return $this->success([
            'reviews' => $reviews->toArray(),
        ], 'Success', 200);
    }
}