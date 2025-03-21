<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;

final readonly class ProductResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function createProduct($_, array $args)
    {
        // Validate input
        $validator = Validator::make($args, [
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'stock' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'product' => null,
            ];
        }

        try {
            // Create the product
            $product = Product::create([
                'name' => $args['name'],
                'price' => $args['price'],
                'description' => $args['description'],
                'stock' => $args['stock'],
                'status' => $args['status'],
            ]);

            return [
                'code' => 200,
                'message' => 'success',
                'product' => $product,
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'product' => null,
            ];
        }
    }

    public function updateProduct($_, array $args)
    {
        // Validate input
        $validator = Validator::make($args, [
            'id' => 'required|exists:products,id', // Ensure the product exists
            'name' => 'string',
            'price' => 'numeric|min:0',
            'description' => 'string|nullable',
            'stock' => 'numeric|min:0',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'product' => null,
            ];
        }

        // Find the product
        $product = Product::find($args['id']);
        if (!$product) {
            return [
                'code' => 404,
                'message' => 'Product not found',
                'product' => null,
            ];
        }

        try {
            // Update the product
            $product->fill([
                'name' => $args['name'] ?? $product->name,
                'price' => $args['price'] ?? $product->price,
                'description' => $args['description'] ?? $product->description,
                'stock' => $args['stock'] ?? $product->stock,
                'status' => $args['status'] ?? $product->status,
            ]);

            $product->save();

            return [
                'code' => 200,
                'message' => 'success',
                'product' => $product,
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'product' => null,
            ];
        }
    }
}