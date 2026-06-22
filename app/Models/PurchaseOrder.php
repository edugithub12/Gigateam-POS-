<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Traits\LogsUserActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use LogsUserActivity;
    use SoftDeletes;
    use BelongsToShop; // provides location(), global shop scope, auto-fill location_id

    protected $fillable = [
        'po_number', 'supplier_id', 'created_by', 'location_id', 'status',
        'total', 'notes', 'expected_date', 'received_at',
    ];

    protected $casts = [
        'total'         => 'decimal:2',
        'expected_date' => 'date',
        'received_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        // BelongsToShop::bootBelongsToShop() handles auto-filling location_id.
        static::creating(function (PurchaseOrder $po) {
            if (empty($po->po_number)) {
                $po->po_number = DocumentSequence::next('purchase_order', activeShopId());
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // location() relationship now provided by BelongsToShop trait

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
