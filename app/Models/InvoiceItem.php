<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'sort_order', 'description',
        'unit', 'unit_price', 'cost_price', 'quantity', 'discount', 'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->total = round(($item->unit_price * $item->quantity) - $item->discount, 2);
        });

        static::saved(function (InvoiceItem $item) {
            $invoice  = $item->invoice;
            $subtotal = $invoice->items()->sum('total');
            $taxable  = $subtotal - $invoice->discount_amount;
            $vat      = $invoice->include_vat ? round($taxable * 0.16, 2) : 0;
            $invoice->withoutEvents(fn () => $invoice->updateQuietly([
                'subtotal'   => $subtotal,
                'vat_amount' => $vat,
                'total'      => $taxable + $vat,
            ]));
        });
    }
}