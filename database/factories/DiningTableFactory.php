<?php

namespace Database\Factories;

use App\Models\DiningTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Tenant factories are used inside app(Tenant::class)->run($restaurant, ...);
 * the restaurant_id is filled in from there.
 *
 * @extends Factory<DiningTable>
 */
class DiningTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'T'.fake()->unique()->numberBetween(1, 9999),
            'area' => 'Salon',
            'capacity' => 4,
        ];
    }
}
