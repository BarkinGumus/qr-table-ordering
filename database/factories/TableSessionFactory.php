<?php

namespace Database\Factories;

use App\Enums\TableSessionStatus;
use App\Models\DiningTable;
use App\Models\TableSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TableSession> */
class TableSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'dining_table_id' => DiningTable::factory(),
            'status' => TableSessionStatus::Pending,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => TableSessionStatus::Open, 'confirmed_at' => now()]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => TableSessionStatus::Closed, 'closed_at' => now()]);
    }
}
