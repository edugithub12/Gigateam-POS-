<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    /**
     * Automatically adds a `where location_id = activeShopId()` clause
     * to every query on models using the BelongsToShop trait.
     *
     * Behaviour:
     * - Super admin with no shop selected (activeShopId() === null): no filter, sees everything.
     * - Anyone else, or super admin WITH a shop selected: filtered to that shop.
     * - Console/queue context with no active shop bound: no filter (so seeders,
     *   scheduled jobs, and artisan commands aren't accidentally scoped to nothing).
     */
    public function apply(Builder $builder, Model $model): void
    {
        // No HTTP request context (console, queue, tests without explicit setup)
        // means there's no concept of "active shop" — don't filter.
        if (! app()->bound('active_shop')) {
            return;
        }

        $shopId = activeShopId();

        // Super admin viewing "All Shops" — activeShopId() is null — sees everything.
        if ($shopId === null) {
            return;
        }

        $builder->where($model->getTable() . '.location_id', $shopId);
    }
}
