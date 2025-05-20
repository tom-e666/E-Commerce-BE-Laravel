<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Brand;
use Faker\Factory as Faker;

class TestDataHelper
{
    /**
     * Create a test product with MongoDB details
     * 
     * @param array $overrides Product attribute overrides
     * @param array $detailOverrides ProductDetail attribute overrides
     * @return Product
     */
    public static function createTestProduct(array $overrides = [], array $detailOverrides = []): Product
    {
        $faker = Faker::create();
        
        // Get or create a brand
        if (isset($overrides['brand_id'])) {
            $brandId = $overrides['brand_id'];
        } else {
            $brand = Brand::first() ?: Brand::create(['name' => 'Test Brand']);
            $brandId = $brand->id;
        }
        
        // Create product in MySQL
        $product = Product::create(array_merge([
            'name' => 'Test ' . $faker->words(2, true),
            'price' => $faker->randomFloat(2, 100, 1000),
            'stock' => $faker->numberBetween(1, 100),
            'status' => true,
            'brand_id' => $brandId,
            'weight' => $faker->randomFloat(2, 0.5, 3),
        ], $overrides));
        
        // Create product details in MongoDB
        $images = isset($detailOverrides['images']) ? $detailOverrides['images'] : [
            "https://picsum.photos/id/" . $faker->numberBetween(1, 1000) . "/800/600",
            "https://picsum.photos/id/" . $faker->numberBetween(1, 1000) . "/800/600",
        ];
        
        $keywords = isset($detailOverrides['keywords']) ? $detailOverrides['keywords'] : [
            strtoupper($faker->word),
            strtoupper($faker->word),
            strtoupper($faker->word),
        ];
        
        $specifications = isset($detailOverrides['specifications']) ? $detailOverrides['specifications'] : [
            ['name' => 'CPU', 'value' => 'Intel Core i7'],
            ['name' => 'RAM', 'value' => '16GB'],
            ['name' => 'Storage', 'value' => '512GB SSD'],
        ];
        
        ProductDetail::create(array_merge([
            'product_id' => (string)$product->id,
            'description' => $faker->paragraph(3),
            'images' => $images,
            'keywords' => $keywords,
            'specifications' => $specifications,
        ], $detailOverrides));
        
        return $product->fresh();
    }
}
