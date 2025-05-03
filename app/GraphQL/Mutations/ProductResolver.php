<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;

final readonly class ProductResolver
{

    use GraphQLResponse;

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
            'stock' => 'required|numeric|min:0',
            'status' => 'required|boolean',
            'brand_id' => 'exists:brands,id|nullable',
            // 'details' => 'required|object',
            // 'details.description' => 'required|string',
            // 'details.images' => 'required|array',
            // 'details.keywords' => 'required|array',
            // 'details.specifications' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }

        try {
            $product = Product::create([
                'name' => $args['name'],
                'price' => $args['price'],
                'stock' => $args['stock'],
                'status' => $args['status'],
                'brand_id' => $args['brand_id'] ?? null,
            ]);
        
            if (!$product) {
                return $this->error('Failed to create product', 500);
            }
        
            $details = $args['details'] ?? null;
            if ($details) {
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
            }
        
            return $this->success([
                'product' => $product,
            ], 'Product created successfully', 200);
        
        } catch (\Exception $e) {
            return $this->error('An error occurred: ' . $e->getMessage(), 500);
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
            'brand_id' => 'exists:brands,id|nullable',
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

        try {
            // Update the product
            $product->fill([
                'name' => $args['name'] ?? $product->name,
                'price' => $args['price'] ?? $product->price,
                'description' => $args['description'] ?? $product->description,
                'stock' => $args['stock'] ?? $product->stock,
                'status' => $args['status'] ?? $product->status,
                'brand_id' => $args['brand_id'] ?? $product->brand_id,
            ]);

            $product->save();

            //Handle product details
            $details = $args['details'] ?? null;
            if ($details) {
                $productDetail = ProductDetail::where('product_id', $product->id)->first();

                if ($productDetail) {
                    // Update existing product details
                    $productDetail->update([
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
                        'description' => $details['description'],
                        'images' => $details['images'],
                        'keywords' => $details['keywords'],
                        'specifications' => $details['specifications'],
                    ]);
                }
            }

            return $this->success([
                'product' => $product,
            ], 'Product updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // public function updateProductDetail($_, array $args)
    // {
    //     $validator=Validator::make($args,[
    //         'product_id'=>'required|exists:products,id',
    //         'description'=>'string',
    //         'images'=>'array',
    //         'keywords'=>'array',
    //         'specifications'=>'array',
    //     ]);
    //     if($validator->fails)
    //     {
    //         return $this->error($validator->errors()->first(), 400);
    //     }

    //     $productDetail=Productdetail::find($args['product_id']);

    //     if(!$productDetail)
    //     {
    //         return $this->error('Product detail not found', 404);
    //     }
    //     try {
    //         $productDetail->fill([
    //             'description'=>$args['description']??$productDetail->description,
    //             'images'=>$args['images']??$productDetail->images,
    //             'keywords'=>$args['keywords']??$productDetail->keywords,
    //             'specifications'=>$args['specifications']??$productDetail->specifications,
    //         ]);

    //         $productDetail->save();

    //         return $this->success([
    //             'product_detail' => $productDetail,
    //         ], 'success', 200);
    //     } catch (\Exception $e) {
    //         return $this->error($e->getMessage(), 500);
    //     }
    // }
}