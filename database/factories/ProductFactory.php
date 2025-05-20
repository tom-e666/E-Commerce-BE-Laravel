<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brandIds = Brand::pluck('id')->toArray();
        if (empty($brandIds)) {
            $brand = Brand::create(['name' => 'Default Brand']);
            $brandIds = [$brand->id];
        }

        return [
            'name' => $this->faker->words(3, true) . ' ' . $this->faker->randomElement(['Laptop', 'Phone', 'Tablet', 'Monitor']),
            'price' => $this->faker->randomFloat(2, 100, 2000),
            'stock' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->boolean(80), // 80% will be active
            'brand_id' => $this->faker->randomElement($brandIds),
            'weight' => $this->faker->randomFloat(2, 0.5, 5),
        ];
    }
}
