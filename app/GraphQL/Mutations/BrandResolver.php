<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Brand;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;

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
            return $this->error($e->getMessage(), 500);
        }
    }
    public function updateBrand($_, array $args)
    {
        $validator = Validator::make($args, [
            'id' => 'required|exists:brands,id',
            'name' => 'required|string|unique:brands,name,' . $args['id'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        try {
            $brand = Brand::find($args['id']);
            $brand->update([
                'name' => $args['name'],
            ]);
            return $this->success([
                'brand' => $brand,
            ], 'success', 200);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function deleteBrand($_, array $args)
    {
        $validator = Validator::make($args, [
            'id' => 'required|exists:brands,id',
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        try {
            $brand = Brand::find($args['id']);
            $brand->delete();
            return $this->success([
                'message' => 'Brand deleted successfully',
            ], 'success', 200);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}