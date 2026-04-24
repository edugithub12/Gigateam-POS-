<?php

namespace App\Models;

use App\Notifications\LowStockNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'brand', 'model_number',
        'unit', 'cost_price', 'selling_price', 'installation_price',
        'stock_quantity', 'low_stock_threshold', 'is_service',
        'is_active', 'barcode', 'image',
    ];

    protected $casts = [
        'cost_price'         => 'decimal:2',
        'selling_price'      => 'decimal:2',
        'installation_price' => 'decimal:2',
        'is_service'         => 'boolean',
        'is_active'          => 'boolean',
    ];

    protected static function booted(): void
    {
        // Fire low stock notification when stock drops at or below threshold
        static::updated(function (Product $product) {
            if (!$product->is_service && $product->wasChanged('stock_quantity')) {
                $old = $product->getOriginal('stock_quantity');
                $new = $product->stock_quantity;

                // Only notify when crossing the threshold downward
                if ($new <= $product->low_stock_threshold && $old > $product->low_stock_threshold) {
                    $admins = User::role('admin')->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new LowStockNotification($product));
                    }
                }
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

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
        return $query->where(function ($q) {
            $q->where('is_service', true)->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', $search)
              ->orWhere('brand', 'like', "%{$search}%");
        });
    }

    public function scopeLowStock($query)
    {
        return $query->where('is_service', false)
                     ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return !$this->is_service && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return !$this->is_service && $this->stock_quantity <= 0;
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
}