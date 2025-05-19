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
        try {
            $page = $args['page'] ?? 1;
            $perPage = $args['per_page'] ?? 20;
        
        $brands = Brand::paginate($perPage, ['*'], 'page', $page);
        
        return $this->success([
            'brands' => $brands->items(),
        ], 'success', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
        
    }
    
    public function getBrand($_, array $args): array
    {
        
        try {
            $brand = Brand::where('id', $args['id'])->first();
        if ($brand === null) {
            return $this->error('Brand not found', 404);
        }
        return $this->success([
            'brand' => $brand,
        ], 'success', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}