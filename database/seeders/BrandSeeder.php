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
            // Using firstOrCreate to avoid duplicate entries
            Brand::firstOrCreate(['name' => $brand]);
        }
        
        // Output success message
        $this->command->info('Brands seeded: ' . Brand::count());
    }
}