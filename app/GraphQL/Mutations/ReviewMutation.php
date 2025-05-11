<?php

namespace App\GraphQL\Mutations;

use App\Models\Review;
use App\GraphQL\Traits\GraphQLResponse;
use Nuwave\Lighthouse\Execution\HttpGraphQLContext;

class ReviewMutation
{
    use GraphQLResponse;

    public function createReview($root, array $args)
    {
        $user= AuthService::Auth();
        if(!$user)
        {
            return $this->error('Unauthorized', 401);
        }
        $productId = OrderItem::find($args['order_item_id'])->product_id;
        if(!$productId)
        {
            return $this->error('Product not found', 404);
        }
        $existingReview = Review::where('user_id', $user->id)
                        ->where('order_item_id', $args['order_item_id'])
                        ->first();
        if ($existingReview) {
             return $this->error('You have already reviewed this purchased product', 400);
        }
        try {
            $review = Review::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'rating' => $args['rating']??5,
                'comment' => $args['comment'] ?? null,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to create review', 500);
        }
        return $this->success([
            'review' => $review->toArray(),
        ], 'Review created successfully', 201);
    }
    public function updateReview($root, array $args)
    {
        $user= AuthService::Auth();
        if(!$user)
        {
            return $this->error('Unauthorized', 401);
        }
        $review = Review::find($args['revew_id']);
        if (!$review) {
            return $this->error('Review not found', 404);
        }
        if ($review->user_id !== $user->id) {
            return $this->error('Unauthorized', 401);
        }
        $review->rating = $args['rating'] ?? $review->rating;
        $review->comment = $args['comment'] ?? $review->comment;
        try {
            $review->save();
        } catch (\Exception $e) {
            return $this->error('Failed to update review', 500);
        }
        return $this->success([], 'Review updated successfully', 200);
    }

    public function deleteReview($root, array $args)
    {
       $user= AuthService::Auth();
        if(!$user)
        {
            return $this->error('Unauthorized', 401);
        }
        $review = Review::find($args['review_id']);
        if (!$review) {
            return $this->error('Review not found', 404);
        }
        if ($review->user_id !== $user->id) {
            return $this->error('Unauthorized', 401);
        }
        try {
            $review->delete();
        } catch (\Exception $e) {
            return $this->error('Failed to delete review', 500);
        }
        return $this->success([], 'Review deleted successfully', 200);
    }
} 