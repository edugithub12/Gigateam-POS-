<?php

namespace App\Models;

use App\Traits\LogsUserActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationStock extends Model
{
    use LogsUserActivity;

    protected $fillable = [
        'product_id', 'location_id', 'quantity', 'low_stock_threshold',
    ];

    protected $casts = [
        'quantity'            => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    protected static function getActivityLogName(): string
    {
        return 'location_stocks';
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }
}