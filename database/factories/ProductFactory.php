<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $word = ucfirst(fake()->unique()->word());

        return [
            'category_id' => Category::factory(),
            'name' => ['tr' => $word, 'en' => $word],
            'price' => fake()->randomFloat(2, 20, 300),
        ];
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['is_available' => false]);
    }
}
