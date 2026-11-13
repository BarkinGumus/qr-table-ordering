<?php

namespace Database\Factories;

use App\Enums\Station;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $word = ucfirst(fake()->unique()->word());

        return [
            'name' => ['tr' => $word, 'en' => $word],
            'station' => Station::Kitchen,
        ];
    }

    public function bar(): static
    {
        return $this->state(fn () => ['station' => Station::Bar]);
    }
}
