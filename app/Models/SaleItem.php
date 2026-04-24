<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'product_name', 'product_sku', 'unit',
        'unit_price', 'cost_price', 'quantity', 'discount', 'total',
        'needs_installation',
    ];

    protected $casts = [
        'unit_price'         => 'decimal:2',
        'cost_price'         => 'decimal:2',
        'discount'           => 'decimal:2',
        'total'              => 'decimal:2',
        'needs_installation' => 'boolean',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}