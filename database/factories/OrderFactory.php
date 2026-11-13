<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\TableSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'table_session_id' => TableSession::factory()->open(),
            'dining_table_id' => fn (array $attributes) => TableSession::findOrFail($attributes['table_session_id'])->dining_table_id,
            'number' => fake()->unique()->numberBetween(1, 999999),
            'status' => OrderStatus::Received,
            'idempotency_key' => (string) Str::uuid(),
            'total' => 0,
        ];
    }
}
