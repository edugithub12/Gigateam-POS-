<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'product_id', 'sort_order', 'description',
        'unit', 'unit_price', 'cost_price', 'quantity', 'discount', 'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (QuotationItem $item) {
            $item->total = round(($item->unit_price * $item->quantity) - $item->discount, 2);
        });
    }
}