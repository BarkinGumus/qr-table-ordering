<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Adds "WHERE restaurant_id = <current restaurant>" to every query on a
 * tenant model. With no restaurant set it throws instead of returning rows.
 */
class RestaurantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = app(Tenant::class);

        if ($tenant->bypassed()) {
            return;
        }

        if (! $tenant->check()) {
            throw MissingTenantException::forModel($model::class);
        }

        $builder->where($model->qualifyColumn('restaurant_id'), $tenant->id());
    }
}
