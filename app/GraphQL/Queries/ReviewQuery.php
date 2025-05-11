<?php

namespace App\GraphQL\Queries;

use App\Models\Review;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;
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
    public function getReviewsByUser($root, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        $reviews = Review::where('user_id', $user->id)->get();

        return $this->success([
            'reviews' => $reviews->toArray(),
        ], 'Success', 200);
    }
    public function getReviewsByProduct($root, array $args)
    {
        if(!isset($args['product_id']))
        {
            return $this->error('product_id is required', 400);
        }
        if(isset($args['amount']))
        {
                $reviews = Review::where('product_id', $args['product_id'])->orderBy('created_at', 'desc')->limit($args['amount']);
                return $this->success([
                    'reviews' => $reviews->toArray(),
                ], 'Success', 200);
        }
        $reviews = Review::where('product_id', $args['product_id'])->get();
        return $this->success([
            'reviews' => $reviews->toArray(),
        ], 'Success', 200);
    }
} 