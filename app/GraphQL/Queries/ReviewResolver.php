<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Review;
use App\Models\Product;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;

final class ReviewResolver
{
    use GraphQLResponse;
    
    /**
     * Get reviews for a specific product
     */
    public function getProductReviews($_, array $args): array
    {
        // Check if product_id is provided
        if (!isset($args['product_id'])) {
            return $this->error('product_id is required', 400);
        }
        
        // Check if product exists in MySQL
        $product = Product::find($args['product_id']);
        if (!$product) {
            return $this->error('Product not found', 404);
        }
        
        try {
            // Query MongoDB collection directly
            $query = Review::where('product_id', (string)$args['product_id']);
            
            // Apply rating filter if provided
            if (isset($args['rating']) && $args['rating'] > 0) {
                $query->where('rating', $args['rating']);
            }
            
            // Apply sorting
            $sortField = $args['sort_field'] ?? 'created_at';
            $sortDirection = $args['sort_direction'] ?? 'desc';
            
            // Validate sort field
            $allowedSortFields = ['rating', 'created_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }
            
            // Validate sort direction
            $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
            
            $query->orderBy($sortField, $sortDirection);
            
            // Get results
            $reviews = $query->get();
            
            // Format the reviews output manually to avoid SQL-MongoDB relationship issues
            $formattedReviews = $reviews->map(function ($review) {
                $user = UserCredential::find($review->user_id);
                $product = Product::find($review->product_id);
                
                return [
                    'id' => (string)$review->_id,
                    'rating' => (int)$review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'user' => $user ? [
                        'id' => (string)$user->id,
                        'username' => $user->username ?? $user->full_name,
                    ] : null,
                    'product' => $product ? [
                        'id' => (string)$product->id,
                        'name' => $product->name,
                        'image' => $product->details && !empty($product->details->images) 
                            ? $product->details->images[0] 
                            : null,
                    ] : null,
                ];
            });
            
            return $this->success([
                'reviews' => $formattedReviews,
                'total' => $reviews->count()
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to get reviews: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get a user's reviews
     */
    public function getUserReviews($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check if viewing another user's reviews
        $userId = $args['user_id'] ?? $user->id;
        if ($userId != $user->id) {
            // Check permission to view other users' reviews
            if (Gate::denies('viewAny', Review::class)) {
                return $this->error('You are not authorized to view other users\' reviews', 403);
            }
        }
        
        try {
            $query = Review::where('user_id', (string)$userId);
            
            // Get results with product info
            $reviews = $query->with(['product', 'product.details'])
                           ->orderBy('created_at', 'desc')
                           ->get();
            
            if ($reviews->isEmpty()) {
                return $this->success([
                    'reviews' => [],
                    'total' => 0
                ], 'No reviews found for this user', 200);
            }
            
            // Format the reviews output
            $formattedReviews = $reviews->map(function ($review) {
                return [
                    'id' => (string)$review->_id,
                    'rating' => (int)$review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'product' => $review->product ? [
                        'id' => (string)$review->product->id,
                        'name' => $review->product->name,
                        'image' => $review->product->details && !empty($review->product->details->images) 
                            ? $review->product->details->images[0] 
                            : null,
                    ] : null,
                ];
            });
            
            return $this->success([
                'reviews' => $formattedReviews,
                'total' => $reviews->count()
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to get user reviews: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get a specific review by ID
     */
    public function getReview($_, array $args): array
    {
        if (!isset($args['review_id'])) {
            return $this->error('review_id is required', 400);
        }
        
        try {
            $review = Review::with(['user', 'product', 'product.details'])->find($args['review_id']);
            
            if (!$review) {
                return $this->error('Review not found', 404);
            }
            
            // Check if user can view this review
            $user = AuthService::Auth();
            if (!$review->product->status && (!$user || Gate::denies('view', $review))) {
                return $this->error('You are not authorized to view this review', 403);
            }
            
            return $this->success([
                'review' => [
                    'id' => (string)$review->_id,
                    'rating' => (int)$review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'user' => $review->user ? [
                        'id' => (string)$review->user->id,
                        'username' => $review->user->username,
                    ] : null,
                    'product' => $review->product ? [
                        'id' => (string)$review->product->id,
                        'name' => $review->product->name,
                        'image' => $review->product->details && !empty($review->product->details->images) 
                            ? $review->product->details->images[0] 
                            : null,
                    ] : null,
                ],
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to get review: ' . $e->getMessage(), 500);
        }
    }

public function getAllReviews($_, array $args): array
{
    // Check authentication
    $user = AuthService::Auth();
    if (!$user) {
        return $this->error('Unauthorized', 401);
    }
    
    // Check if user has permission to view all reviews
    if (Gate::denies('viewAny', Review::class)) {
        return $this->error('You are not authorized to view all reviews', 403);
    }
    
    try {
        $query = Review::query();
        
        // Apply product filter if provided
        if (isset($args['product_id']) && !empty($args['product_id'])) {
            $query->where('product_id', (string)$args['product_id']);
        }
        
        // Apply user filter if provided
        if (isset($args['user_id']) && !empty($args['user_id'])) {
            $query->where('user_id', (string)$args['user_id']);
        }
        
        // Apply rating filter if provided
        if (isset($args['rating']) && $args['rating'] > 0) {
            $query->where('rating', $args['rating']);
        }
        
        // Apply date range filter if provided
        if (isset($args['date_from'])) {
            $query->where('created_at', '>=', $args['date_from']);
        }
        
        if (isset($args['date_to'])) {
            $query->where('created_at', '<=', $args['date_to']);
        }
        
        // Apply sorting
        $sortField = $args['sort_field'] ?? 'created_at';
        $sortDirection = $args['sort_direction'] ?? 'desc';
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['rating', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        // Validate sort direction
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortField, $sortDirection);
        
        // Get results with related data
        $reviews = $query->with(['user', 'product', 'product.details'])->get();
        
        if ($reviews->isEmpty()) {
            return $this->success([
                'reviews' => [],
                'total' => 0
            ], 'No reviews found', 200);
        }
        
        // Format the reviews output consistently with other methods
        $formattedReviews = $reviews->map(function ($review) {
            return [
                'id' => (string)$review->_id,
                'rating' => (int)$review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'user' => $review->user ? [
                    'id' => (string)$review->user->id,
                    'username' => $review->user->username,
                ] : null,
                'product' => $review->product ? [
                    'id' => (string)$review->product->id,
                    'name' => $review->product->name,
                    'image' => $review->product->details && !empty($review->product->details->images) 
                        ? $review->product->details->images[0] 
                        : null,
                ] : null,
            ];
        });
        
        return $this->success([
            'reviews' => $formattedReviews,
            'total' => $reviews->count()
        ], 'Success', 200);
    } catch (\Exception $e) {
        return $this->error('Failed to get reviews: ' . $e->getMessage(), 500);
    }
}

   
}