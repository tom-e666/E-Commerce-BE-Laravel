<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run()
    {
        $brands = [
            'Apple',
            'Samsung',
            'Sony',
            'LG',
            'Google',
            'Xiaomi',
            'OnePlus',
            'HP',
            'Dell',
            'Asus'
        ];
        
        foreach ($brands as $brand) {
            Brand::create(['name' => $brand]);
        }
    }
}