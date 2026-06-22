<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Traits\LogsUserActivity;
use App\Notifications\LowStockNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use LogsUserActivity;
    use SoftDeletes;
    use BelongsToShop; // provides location(), global shop scope, auto-fill location_id

    protected $fillable = [
        // location_id must be mass-assignable: most of the time the
        // BelongsToShop::creating() hook auto-fills it from activeShopId(),
        // but some flows (e.g. StockTransfer::moveStock() auto-creating a
        // product in the RECEIVING shop's catalog) need to set it explicitly
        // to a shop that may differ from whichever shop is currently active.
        'location_id',
        'category_id', 'name', 'sku', 'description', 'brand', 'model_number',
        'unit', 'cost_price', 'selling_price', 'installation_price',
        'quantity', 'reorder_point', 'max_stock_level',
        'is_service', 'is_active', 'barcode', 'image',
    ];

    protected $casts = [
        'cost_price'         => 'decimal:2',
        'selling_price'      => 'decimal:2',
        'installation_price' => 'decimal:2',
        'is_service'         => 'boolean',
        'is_active'          => 'boolean',
    ];

    // ── Mutators ──────────────────────────────────────────────────────────────

    public function setCostPriceAttribute($value): void
    {
        $this->attributes['cost_price'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setSellingPriceAttribute($value): void
    {
        $this->attributes['selling_price'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setInstallationPriceAttribute($value): void
    {
        $this->attributes['installation_price'] = ($value === '' || $value === null) ? 0 : $value;
    }

    // ── Lifecycle hooks ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::updated(function (Product $product) {
            if (! $product->is_service && $product->wasChanged('quantity')) {
                $old = $product->getOriginal('quantity');
                $new = $product->quantity;

                if ($new <= $product->reorder_point && $old > $product->reorder_point) {
                    $managers = User::whereHas('activeLocations', fn ($q) =>
                        $q->where('location_id', $product->location_id)
                          ->where('location_user.shop_role', 'shop_manager')
                    )->get();

                    $admins = User::role('admin')->get()->filter(fn (User $u) => $u->isSuperAdmin());

                    foreach ($managers->merge($admins)->unique('id') as $approver) {
                        $approver->notify(new LowStockNotification($product));
                    }
                }
            }
        });
    }

    protected static function getActivityLogName(): string
    {
        return 'products';
    }

    // ── Relationships ─────────────────────────────────────────────────────────
    // location() relationship provided by BelongsToShop trait

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(fn ($q) =>
            $q->where('is_service', true)->orWhere('quantity', '>', 0)
        );
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(fn ($q) =>
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', $search)
              ->orWhere('brand', 'like', "%{$search}%")
        );
    }

    public function scopeLowStock($query)
    {
        return $query->where('is_service', false)
                     ->where('quantity', '>', 0)
                     ->whereColumn('quantity', '<=', 'reorder_point');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('is_service', false)->where('quantity', '<=', 0);
    }

    public function scopeMatchingElsewhere($query, Product $product)
    {
        return $query->where('location_id', '!=', $product->location_id)
            ->where('is_active', true)
            ->where(function ($q) use ($product) {
                if ($product->barcode) {
                    $q->orWhere('barcode', $product->barcode);
                }
                $q->orWhere('sku', $product->sku);
            });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return ! $this->is_service && $this->quantity > 0 && $this->quantity <= $this->reorder_point;
    }

    public function isOutOfStock(): bool
    {
        return ! $this->is_service && $this->quantity <= 0;
    }

    public function profitMargin(): float
    {
        if ($this->selling_price == 0) return 0;
        return round((($this->selling_price - $this->cost_price) / $this->selling_price) * 100, 1);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->is_service) return 'service';
        if ($this->isOutOfStock()) return 'out_of_stock';
        if ($this->isLowStock()) return 'low_stock';
        return 'in_stock';
    }

    public function findMatchesInOtherShops()
    {
        return static::withoutShopScope()
            ->matchingElsewhere($this)
            ->with('location')
            ->get();
    }
}