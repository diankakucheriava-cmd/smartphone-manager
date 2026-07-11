<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'product_id'     => Product::factory(),
            'rating'         => fake()->numberBetween(1, 5),
            'comment'        => fake()->sentence(),
            'reviewed_at'    => now()->subDays(fake()->numberBetween(1, 365))->toIso8601String(),
            'reviewer_name'  => fake()->name(),
            'reviewer_email' => fake()->safeEmail(),
        ];
    }
}
