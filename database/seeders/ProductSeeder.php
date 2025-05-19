<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $brands = Brand::all()->pluck('id', 'name')->toArray();
        
        // Product data with details
        $products = [
            [
                'name' => 'iPhone 13 Pro',
                'price' => 999.99,
                'stock' => 50,
                'status' => true,
                'brand_name' => 'Apple',
                'details' => [
                    'description' => 'The latest iPhone with A15 Bionic chip, Pro camera system, and Super Retina XDR display with ProMotion.',
                    'specifications' => [
                        ['name' => 'Display', 'value' => '6.1-inch Super Retina XDR'],
                        ['name' => 'Processor', 'value' => 'A15 Bionic'],
                        ['name' => 'Storage', 'value' => '128GB'],
                        ['name' => 'Camera', 'value' => 'Triple 12MP Pro camera system']
                    ],
                    'images' => [
                        'https://example.com/images/iphone13pro_1.jpg',
                        'https://example.com/images/iphone13pro_2.jpg'
                    ],
                    'keywords' => ['iPhone', 'Apple', 'smartphone', 'iOS']
                ]
            ],
            [
                'name' => 'Samsung Galaxy S21',
                'price' => 799.99,
                'stock' => 45,
                'status' => true,
                'brand_name' => 'Samsung',
                'details' => [
                    'description' => 'Flagship Android smartphone with powerful camera, Snapdragon processor, and dynamic AMOLED display.',
                    'specifications' => [
                        ['name' => 'Display', 'value' => '6.2-inch Dynamic AMOLED'],
                        ['name' => 'Processor', 'value' => 'Snapdragon 888'],
                        ['name' => 'Storage', 'value' => '128GB'],
                        ['name' => 'Camera', 'value' => 'Triple camera system']
                    ],
                    'images' => [
                        'https://example.com/images/galaxys21_1.jpg',
                        'https://example.com/images/galaxys21_2.jpg'
                    ],
                    'keywords' => ['Samsung', 'Galaxy', 'Android', 'smartphone']
                ]
            ],
            [
                'name' => 'Sony WH-1000XM4',
                'price' => 349.99,
                'stock' => 30,
                'status' => true,
                'brand_name' => 'Sony',
                'details' => [
                    'description' => 'Industry-leading noise cancelling wireless headphones with exceptional sound quality.',
                    'specifications' => [
                        ['name' => 'Type', 'value' => 'Over-ear'],
                        ['name' => 'Battery Life', 'value' => '30 hours'],
                        ['name' => 'Connectivity', 'value' => 'Bluetooth 5.0'],
                        ['name' => 'Features', 'value' => 'Active Noise Cancellation, Touch controls']
                    ],
                    'images' => [
                        'https://example.com/images/sony_wh1000xm4_1.jpg',
                        'https://example.com/images/sony_wh1000xm4_2.jpg'
                    ],
                    'keywords' => ['Sony', 'headphones', 'noise-cancelling', 'wireless']
                ]
            ],
            // Add more products as needed
        ];
        
        // Create products with details
        foreach ($products as $productData) {
            // Get brand ID from name
            $brandName = $productData['brand_name'];
            $brandId = $brands[$brandName] ?? null;
            
            if (!$brandId) {
                continue; // Skip if brand not found
            }
            
            // Create product
            $product = Product::create([
                'name' => $productData['name'],
                'price' => $productData['price'],
                'stock' => $productData['stock'],
                'status' => $productData['status'],
                'brand_id' => $brandId
            ]);
            
            // Create product details
            ProductDetail::create([
                'product_id' => $product->id,
                'description' => $productData['details']['description'],
                'specifications' => $productData['details']['specifications'],
                'images' => $productData['details']['images'],
                'keywords' => $productData['details']['keywords']
            ]);
        }
    }
}