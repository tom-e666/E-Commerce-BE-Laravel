<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Brand;
use App\GraphQL\Traits\GraphQLResponse;

final readonly class BrandResolver{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function getBrands($_, array $args): array
    {
        $brands = Brand::all();
        if ($brands->isEmpty()) {
            return $this->error('No brands found', 404);
        }
        return $this->success([
            'brands' => $brands,
        ], 'success', 200);
    }
    
    public function getBrand($_, array $args): array
    {
        $brand = Brand::where('id', $args['id'])->first();
        if ($brand === null) {
            return $this->error('Brand not found', 404);
        }
        return $this->success([
            'brand' => $brand,
        ], 'success', 200);
    }
}