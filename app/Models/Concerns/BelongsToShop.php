<?php

namespace App\Models\Concerns;

use App\Models\Location;
use App\Models\Scopes\ShopScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToShop
{
    /**
     * Boot the trait — registers the global scope and auto-fills
     * location_id on create when it's not explicitly provided.
     */
    public static function bootBelongsToShop(): void
    {
        static::addGlobalScope(new ShopScope);

        static::creating(function ($model) {
            if (empty($model->location_id)) {
                $shopId = activeShopId();

                if ($shopId) {
                    $model->location_id = $shopId;
                }
                // If $shopId is null (super admin, all shops), location_id
                // must be set explicitly by the form/controller — we don't guess.
            }
        });
    }

    /**
     * The shop this record belongs to.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Escape hatch — query across ALL shops regardless of active shop context.
     * Use sparingly: cross-shop reports, super-admin exports, background jobs
     * that intentionally need to see everything.
     *
     * Usage: Sale::withoutShopScope()->where(...)->get();
     */
    public static function withoutShopScope(): Builder
    {
        return static::withoutGlobalScope(ShopScope::class);
    }

    /**
     * Explicitly scope to a specific shop, bypassing the active-shop context.
     * Useful for cross-shop comparisons where you need one shop at a time.
     *
     * Usage: Sale::forShop($otherShopId)->get();
     */
    public static function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->withoutGlobalScope(ShopScope::class)
                      ->where((new static)->getTable() . '.location_id', $shopId);
    }
}
