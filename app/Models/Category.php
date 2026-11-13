<?php

namespace App\Models;

use App\Enums\Station;
use App\Models\Concerns\HasTranslations;
use App\Tenancy\BelongsToRestaurant;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'station', 'image_path', 'sort_order', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToRestaurant, HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'station' => Station::class,
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
