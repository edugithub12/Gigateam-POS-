<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardItem extends Model
{
    protected $fillable = [
        'job_card_id', 'product_id', 'description', 'unit',
        'quantity', 'unit_price', 'total', 'source',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (JobCardItem $item) {
            $item->total = round($item->unit_price * $item->quantity, 2);
        });
    }
}