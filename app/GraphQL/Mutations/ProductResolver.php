<?php

namespace App\GraphQL\Mutations;

use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;

final class ProductResolver
{
    use GraphQLResponse;

    /**
     * Create a new product
     *
     * @param mixed $_ Root value (not used)
     * @param array $args Mutation arguments
     * @return array Response with created product or error
     */
    public function createProduct($_, array $args)
    {
        // Check authentication
        $user = AuthService::Auth();

        // Check authorization using policy
        if (Gate::denies('create', Product::class)) {
            return $this->error('You are not authorized to create products', 403);
        }

        // Validate input
        $validator = Validator::make($args, [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'default_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => 'required|boolean',
            'brand_id' => 'required|exists:brands,id',
            'weight' => 'required|numeric|min:0',
            'details' => 'required|array',
            'details.description' => 'required|string',
            'details.images' => 'required|array|min:1',
            'details.keywords' => 'required|array',
            'details.specifications' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        try {
            // Create the product
            $product = Product::create([
                'name' => $args['name'],
                'price' => $args['price'],
                'default_price' => $args['default_price'] ?? null,
                'stock' => $args['stock'],
                'status' => $args['status'],
                'brand_id' => $args['brand_id'],
                'weight' => $args['weight'],
            ]);
        
            if (!$product) {
                return $this->error('Failed to create product', 500);
            }
        
            $details = $args['details'];
            $productDetail = ProductDetail::create([
                'product_id' => $product->id,
                'description' => $details['description'],
                'images' => $details['images'],
                'keywords' => $details['keywords'],
                'specifications' => $details['specifications'],
            ]);
            if (!$productDetail) {
                $product->delete();
                return $this->error('Failed to create product details', 500);
            }
        
            // Load the product with its details for the response
            $product->load('details');
            
            return $this->success([
                'product' => $this->formatProductResponse($product),
            ], 'Product created successfully', 200);
        
        } catch (\Exception $e) {
            return $this->error('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing product
     *
     * @param mixed $_ Root value (not used)
     * @param array $args Mutation arguments
     * @return array Response with updated product or error
     */
    public function updateProduct($_, array $args)
    {
        // Check authentication
        $user = AuthService::Auth();

        // Validate input
        $validator = Validator::make($args, [
            'id' => 'required|exists:products,id',
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
            'default_price' => 'nullable|numeric|min:0',
            'stock' => 'integer|min:0',
            'status' => 'boolean',
            'brand_id' => 'exists:brands,id',
            'weight' => 'numeric|min:0|nullable',
            'details' => 'array|nullable',
            'details.description' => 'string|nullable',
            'details.images' => 'array|nullable',
            'details.keywords' => 'array|nullable',
            'details.specifications' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        // Find the product
        $product = Product::find($args['id']);
        if (!$product) {
            return $this->error('Product not found', 404);
        }
        
        // Check authorization using policy
        if (Gate::denies('update', $product)) {
            return $this->error('You are not authorized to update this product', 403);
        }

        try {
            // Update the product
            $product->fill([
                'name' => $args['name'] ?? $product->name,
                'price' => $args['price'] ?? $product->price,
                'default_price' => isset($args['default_price']) ? $args['default_price'] : $product->default_price,
                'stock' => $args['stock'] ?? $product->stock,
                'status' => isset($args['status']) ? $args['status'] : $product->status,
                'brand_id' => $args['brand_id'] ?? $product->brand_id,
                'weight' => $args['weight'] ?? $product->weight,
            ]);

            $product->save();

            // Handle product details
            $details = $args['details'] ?? null;
            if ($details) {
                $productDetail = ProductDetail::where('product_id', $product->id)->first();

                if ($productDetail) {
                    // Update existing product details
                    $productDetail->fill([
                        'description' => $details['description'] ?? $productDetail->description,
                        'images' => $details['images'] ?? $productDetail->images,
                        'keywords' => $details['keywords'] ?? $productDetail->keywords,
                        'specifications' => $details['specifications'] ?? $productDetail->specifications,
                    ]);
                    $productDetail->save();
                } else {
                    // Create new product details if they don't exist
                    ProductDetail::create([
                        'product_id' => $product->id,
                        'description' => $details['description'] ?? '',
                        'images' => $details['images'] ?? [],
                        'keywords' => $details['keywords'] ?? [],
                        'specifications' => $details['specifications'] ?? [],
                    ]);
                }
            }

            // Load the product with its details for the response
            $product->load('details');
            
            return $this->success([
                'product' => $this->formatProductResponse($product),
            ], 'Product updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to update product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a product
     *
     * @param mixed $_ Root value (not used)
     * @param array $args Mutation arguments
     * @return array Response with success message or error
     */
    public function deleteProduct($_, array $args)
    {
        // Check authentication
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $validator = Validator::make($args, [
            'id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        try {
            $product = Product::find($args['id']);
            
            if (!$product) {
                return $this->error('Product not found', 404);
            }
            
            // Check authorization using policy
            if (Gate::denies('delete', $product)) {
                return $this->error('You are not authorized to delete this product', 403);
            }
            
            // Check if product has order history
            $hasOrders = OrderItem::where('product_id', $product->id)->exists();
            if ($hasOrders) {
                return $this->error(
                    'Cannot delete product with order history. Consider marking it as inactive instead.', 
                    400
                );
            }
            
            // Delete product details first (foreign key constraint)
            $productDetail = ProductDetail::where('product_id', $product->id)->first();
            if ($productDetail) {
                $productDetail->delete();
            }
            
            // Delete the product
            $product->delete();
            
            return $this->success([], 'Product deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Format product data for response
     *
     * @param Product $product Product model
     * @return array Formatted product data
     */
    private function formatProductResponse(Product $product): array
    {
        $productDetail = $product->details;
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'default_price' => (float) $product->default_price,
            'stock' => (int) $product->stock,
            'status' => (bool) $product->status,
            'brand_id' => $product->brand_id,
            'weight' => (float) $product->weight,
            'details' => $productDetail ? [
                'description' => $productDetail->description,
                'specifications' => $productDetail->specifications,
                'images' => $productDetail->images,
                'keywords' => $productDetail->keywords,
            ] : null,
        ];
    }
}