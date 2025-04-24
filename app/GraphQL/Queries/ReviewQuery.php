<?php

namespace App\GraphQL\Queries;

use App\Models\Review;
use App\GraphQL\Traits\GraphQLResponse;

class ReviewQuery
{
    use GraphQLResponse;

    public function getReviews($root, array $args)
    {
        $query = Review::query();
        
        if (isset($args['product_id'])) {
            $query->where('product_id', $args['product_id']);
        }

        $reviews = $query->get();

        return $this->success([
            'reviews' => $reviews->toArray(),
        ], 'Success', 200);
    }

    public function getReview($root, array $args)
    {
        $review = Review::find($args['id']);
        
        if (!$review) {
            return $this->error('Review not found', 404);
        }

        return $this->success([
            'review' => $review->toArray(),
        ], 'Success', 200);
    }
} 