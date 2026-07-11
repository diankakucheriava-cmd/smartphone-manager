<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'external_id'            => null,
            'title'                  => fake()->words(3, true),
            'description'            => fake()->sentence(),
            'brand_id'               => Brand::factory(),
            'category_id'            => Category::factory(),
            'price'                  => fake()->randomFloat(2, 50, 2000),
            'discount_percentage'    => fake()->randomFloat(2, 0, 50),
            'rating'                 => fake()->randomFloat(2, 0, 5),
            'stock'                  => fake()->numberBetween(0, 100),
            'sku'                    => fake()->unique()->bothify('???-###-???'),
            'weight'                 => fake()->randomFloat(2, 0.1, 5),
            'width'                  => fake()->randomFloat(2, 5, 20),
            'height'                 => fake()->randomFloat(2, 10, 30),
            'depth'                  => fake()->randomFloat(2, 1, 15),
            'warranty_information'   => '1 year warranty',
            'shipping_information'   => 'Ships in 3-5 days',
            'availability_status'    => 'In Stock',
            'return_policy'          => '30 days return policy',
            'minimum_order_quantity' => 1,
            'barcode'                => fake()->unique()->numerify('##############'),
            'qr_code'                => fake()->url(),
            'thumbnail'              => fake()->imageUrl(),
        ];
    }

    public function fromExternal(): static
    {
        return $this->state(fn() => ['external_id' => fake()->unique()->numberBetween(1, 99999)]);
    }
}
