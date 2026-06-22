<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'phone', 'address', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static array $types = [
        'shop'      => 'Shop',
        'warehouse' => 'Warehouse',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * Users assigned to this location via the pivot table.
     * Changed from hasMany (used old location_id column) to belongsToMany.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'location_user')
                    ->withPivot(['shop_role', 'is_primary', 'is_active'])
                    ->withTimestamps();
    }

    /**
     * Only active user assignments.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Products belonging to this shop. Each shop owns its own independent
     * catalog now — stock lives directly on Product.quantity, there's no
     * separate location_stocks join table anymore.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'location_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockTransfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_location_id');
    }

    public function stockTransfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_location_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeShops($query)
    {
        return $query->where('type', 'shop');
    }

    public function scopeWarehouses($query)
    {
        return $query->where('type', 'warehouse');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isWarehouse(): bool
    {
        return $this->type === 'warehouse';
    }

    public function isShop(): bool
    {
        return $this->type === 'shop';
    }
}