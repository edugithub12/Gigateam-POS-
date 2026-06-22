<?php

namespace App\Models;

use App\Traits\LogsUserActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use LogsUserActivity;

    protected $fillable = [
        'product_id',
        'location_id',
        'type',       // sale, transfer_in, transfer_out, adjustment, purchase, return
        'source',     // sale, stock_transfer, manual
        'source_id',
        'quantity',   // negative = out, positive = in
        'stock_before',
        'stock_after',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'stock_before' => 'integer',
        'stock_after'  => 'integer',
    ];

    public static array $types = [
        'sale'         => 'Sale',
        'transfer_in'  => 'Transfer In',
        'transfer_out' => 'Transfer Out',
        'adjustment'   => 'Adjustment',
        'purchase'     => 'Purchase',
        'return'       => 'Return',
    ];

    protected static function getActivityLogName(): string
    {
        return 'stock';
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}