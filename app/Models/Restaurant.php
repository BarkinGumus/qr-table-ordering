<?php

namespace App\Models;

use App\Enums\RestaurantStatus;
use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The tenant. Every restaurant-owned row points at one of these.
 */
#[Fillable([
    'name', 'slug', 'default_locale', 'locales', 'currency', 'timezone',
    'confirm_first_order', 'ordering_enabled', 'status',
])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'locales' => 'array',
            'confirm_first_order' => 'boolean',
            'ordering_enabled' => 'boolean',
            'status' => RestaurantStatus::class,
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === RestaurantStatus::Active;
    }
}
