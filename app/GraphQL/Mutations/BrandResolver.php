<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Brand;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;

final readonly class BrandResolver
{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function createBrand($_, array $args)
    {
        $validator = Validator::make($args, [
            'name' => 'required|string|unique:brands,name',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        try {
            $brand = Brand::create([
                'name' => $args['name'],
            ]);
            return $this->success([
                'brand' => $brand,
            ], 'success', 200);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}