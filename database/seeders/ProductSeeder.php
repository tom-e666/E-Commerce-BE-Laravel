<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Brand;
use App\Models\Category;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        
        // Create some brands if none exist
        if (Brand::count() == 0) {
            $brands = [
                ['name' => 'Apple'],
                ['name' => 'Samsung'],
                ['name' => 'Dell'],
                ['name' => 'MSI'],
                ['name' => 'Asus'],
                ['name' => 'Lenovo'],
                ['name' => 'HP'],
                ['name' => 'Xiaomi'],
            ];
            
            foreach ($brands as $brand) {
                Brand::create($brand);
            }
        }
        
        // Get all brand IDs
        $brandIds = Brand::pluck('id')->toArray();
        
        // Clear any existing MongoDB product details to avoid duplicates
        DB::connection('mongodb')->table('product_detail')->delete();
        
        // Create 50 random products
        $products = [];
        $productDetails = [];
        
        for ($i = 0; $i < 50; $i++) {
            // Create the SQL product
            $product = new Product([
                'name' => $faker->words(3, true) . ' ' . $faker->randomElement(['Laptop', 'Phone', 'Tablet', 'Monitor']),
                'price' => $faker->randomFloat(2, 100, 2000),
                'stock' => $faker->numberBetween(0, 100),
                'status' => $faker->boolean(80), 
                'brand_id' => $faker->randomElement($brandIds),
                'weight' => $faker->randomFloat(2, 0.5, 5),
            ]);
            
            $product->save();
            $products[] = $product;
            
            // Create MongoDB product details
            $images = [];
            $imageCount = $faker->numberBetween(1, 5);
            for ($j = 0; $j < $imageCount; $j++) {
                $images[] = "https://picsum.photos/id/" . $faker->numberBetween(1, 1000) . "/800/600";
            }
            $specifications = [];
            $specCount = $faker->numberBetween(3, 8);
            for ($j = 0; $j < $specCount; $j++) {
                $specifications[] = [
                    'name' => $faker->word,
                    'value' => $faker->sentence(3),
                ];
            }
            
            $keywords = [];
            $keywordCount = $faker->numberBetween(3, 8);
            for ($j = 0; $j < $keywordCount; $j++) {
                $keywords[] = strtoupper($faker->word);
            }
            
            $productDetail = new ProductDetail([
                'product_id' => (string)$product->id,
                'description' => $faker->paragraphs(3, true),
                'images' => $images,
                'keywords' => $keywords,
                'specifications' => $specifications,
            ]);
            
            $productDetail->save();
            $productDetails[] = $productDetail;
            
            $this->command->info("Created product: {$product->name}");
        }
        
        $this->command->info('Created ' . count($products) . ' products with details');
    }
}