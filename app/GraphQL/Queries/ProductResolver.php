<?php

namespace App\GraphQL\Queries;

final readonly class ProductQuery
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
        
    }
    public function getProducts($_, array $args): array
    {
        $products = Product::where('status', true)->get();
        $products->load('details');
        return [
            'code'=> 200,
            'message'=> 'success',
            'products'=> $products->toArray(),
        ];
    }
    public function getProduct($_, array $args): array
    {
        if(!isset($args['id']))
        {
            return [
                'code'=> 400,
                'message'=> 'id is required',
                'product'=> null,
            ];
        }
        $product = Product::find($args['id']);
        if($product===null)
        {
            return [
                'code'=> 404,
                'message'=> 'product not found',
                'product'=> null,
            ];
        }
        if($product->status===false)
        {
            return [
                'code'=> 404,
                'message'=> 'product not available',
                'product'=> null,
            ];
        }

        return [
            'code'=> 200,
            'message'=> 'success',
            'product'=> $product->load('details')->toArray(),
            'reviews'=> $product->details->recentReviews(),
        ];
    }
    
}
