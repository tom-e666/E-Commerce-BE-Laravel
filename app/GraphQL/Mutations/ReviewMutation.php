<?php

namespace App\GraphQL\Mutations;

use App\Models\Review;
use App\GraphQL\Traits\GraphQLResponse;
use Nuwave\Lighthouse\Execution\HttpGraphQLContext;

class ReviewMutation
{
    use GraphQLResponse;

    public function createReview($root, array $args, HttpGraphQLContext $context)
    {
        $input = $args['input'];
        $user = $context->user();

        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $review = new Review([
            'product_id' => $input['product_id'],
            'user_id' => $user->id,
            'rating' => $input['rating'],
            'comment' => $input['comment'] ?? null,
        ]);

        $review->save();

        return $this->success([
            'review' => $review->toArray(),
        ], 'Review created successfully', 201);
    }

    public function updateReview($root, array $args, HttpGraphQLContext $context)
    {
        $review = Review::find($args['id']);
        
        if (!$review) {
            return $this->error('Review not found', 404);
        }

        $user = $context->user();
        if (!$user || $review->user_id !== $user->id) {
            return $this->error('Unauthorized', 401);
        }

        $input = $args['input'];
        $review->update([
            'rating' => $input['rating'],
            'comment' => $input['comment'] ?? $review->comment,
        ]);

        return $this->success([
            'review' => $review->toArray(),
        ], 'Review updated successfully', 200);
    }

    public function deleteReview($root, array $args, HttpGraphQLContext $context)
    {
        $review = Review::find($args['id']);
        
        if (!$review) {
            return $this->error('Review not found', 404);
        }

        $user = $context->user();
        if (!$user || $review->user_id !== $user->id) {
            return $this->error('Unauthorized', 401);
        }

        $review->delete();

        return $this->success([], 'Review deleted successfully', 200);
    }
} 