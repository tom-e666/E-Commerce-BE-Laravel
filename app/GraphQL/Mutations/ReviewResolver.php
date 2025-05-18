<?php

namespace App\GraphQL\Mutations;

use App\Models\Review;
use App\GraphQL\Traits\GraphQLResponse;
use App\Models\OrderItem;
use App\Services\AuthService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ReviewResolver
{
    use GraphQLResponse;

    /**
     * Create a review for a purchased product
     */
    public function createReview($root, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Validate order item exists and belongs to the user
        $orderItem = OrderItem::find($args['order_item_id']);
        if (!$orderItem) {
            return $this->error('Order item not found', 404);
        }
        
        // Verify the order item belongs to the user
        if ($orderItem->order->user_id !== $user->id) {
            return $this->error('You can only review products you have purchased', 403);
        }
        
        // Verify the product exists
        $productId = $orderItem->product_id;
        if (!$productId) {
            return $this->error('Product not found', 404);
        }
        
        // Check if user already reviewed this item
        $existingReview = Review::where('user_id', $user->id)
                             ->where('order_item_id', $args['order_item_id'])
                             ->first();
        if ($existingReview) {
             return $this->error('You have already reviewed this purchased product', 400);
        }
        
        // Validate rating
        if (isset($args['rating']) && ($args['rating'] < 1 || $args['rating'] > 5)) {
            return $this->error('Rating must be between 1 and 5', 400);
        }
        
        try {
            $review = Review::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'order_item_id' => $args['order_item_id'],
                'rating' => $args['rating'] ?? 5,
                'comment' => $args['comment'] ?? null,
            ]);
            
            return $this->success([
                'review' => $review,
            ], 'Review created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create review: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update an existing review
     */
    public function updateReview($root, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $review = Review::find($args['review_id']);
        if (!$review) {
            return $this->error('Review not found', 404);
        }
        
        // Use policy for authorization
        if (Gate::denies('update', $review)) {
            return $this->error('You are not authorized to update this review', 403);
        }
        
        // Validate rating if provided
        if (isset($args['rating']) && ($args['rating'] < 1 || $args['rating'] > 5)) {
            return $this->error('Rating must be between 1 and 5', 400);
        }
        
        try {
            $review->rating = $args['rating'] ?? $review->rating;
            $review->comment = $args['comment'] ?? $review->comment;
            $review->save();
            
            return $this->success([
                'review' => $review,
            ], 'Review updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to update review: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete an existing review
     */
    public function deleteReview($root, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $review = Review::find($args['review_id']);
        if (!$review) {
            return $this->error('Review not found', 404);
        }
        
        // Use policy for authorization
        if (Gate::denies('delete', $review)) {
            return $this->error('You are not authorized to delete this review', 403);
        }
        
        try {
            $review->delete();
            return $this->success([], 'Review deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to delete review: ' . $e->getMessage(), 500);
        }
    }
}