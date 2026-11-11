<?php

namespace Database\Factories;

use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Restaurant> */
class RestaurantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'default_locale' => 'tr',
            'locales' => ['tr', 'en'],
            'currency' => 'TRY',
            'timezone' => 'Europe/Istanbul',
            'confirm_first_order' => true,
            'ordering_enabled' => true,
            'status' => RestaurantStatus::Active,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => RestaurantStatus::Suspended]);
    }
}
