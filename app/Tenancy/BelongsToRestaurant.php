<?php

namespace App\Tenancy;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Put this on every model whose table has a restaurant_id column.
 *
 * - Reads are filtered to the current restaurant.
 * - New rows get the current restaurant's id automatically.
 * - A row can never be created for, or moved to, another restaurant.
 */
trait BelongsToRestaurant
{
    public static function bootBelongsToRestaurant(): void
    {
        static::addGlobalScope(new RestaurantScope);

        static::creating(function (Model $model): void {
            $tenant = app(Tenant::class);

            if ($tenant->bypassed()) {
                if ($model->restaurant_id === null) {
                    throw MissingTenantException::forModel($model::class);
                }

                return;
            }

            if (! $tenant->check()) {
                throw MissingTenantException::forModel($model::class);
            }

            if ($model->restaurant_id === null) {
                $model->restaurant_id = $tenant->id();
            } elseif ((int) $model->restaurant_id !== $tenant->id()) {
                throw TenantMismatchException::forModel($model::class);
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('restaurant_id')) {
                throw TenantMismatchException::forModel($model::class);
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
