<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Enums\Station;
use App\Tenancy\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * product_name and unit_price are copied when the order is placed, so a
 * later rename or price change never rewrites an old bill. product_id may
 * be NULL when the product row no longer exists.
 */
#[Fillable([
    'order_id', 'product_id', 'product_name', 'unit_price', 'quantity',
    'note', 'station', 'status', 'status_changed_at', 'cancel_reason',
])]
class OrderItem extends Model
{
    use BelongsToRestaurant;

    protected $attributes = ['status' => 'received'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'station' => Station::class,
            'status' => OrderItemStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
