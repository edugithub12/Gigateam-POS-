<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

// ── Supplier ─────────────────────────────────────────────────────────────────

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'contact_person', 'phone', 'email',
        'address', 'kra_pin', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}

// ── PurchaseOrder ─────────────────────────────────────────────────────────────

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'po_number', 'supplier_id', 'created_by', 'status',
        'total', 'notes', 'expected_date', 'received_at',
    ];

    protected $casts = [
        'total'         => 'decimal:2',
        'expected_date' => 'date',
        'received_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $po) {
            if (empty($po->po_number)) {
                $seq  = DB::table('document_sequences')->where('type', 'purchase_order')->first();
                $next = ($seq->last_number ?? 0) + 1;
                DB::table('document_sequences')->where('type', 'purchase_order')
                    ->update(['last_number' => $next, 'updated_at' => now()]);
                $year  = now()->format('Y');
                $month = now()->format('m');
                $po->po_number = "PO-{$year}{$month}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
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

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}

// ── PurchaseOrderItem ─────────────────────────────────────────────────────────

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id',
        'quantity_ordered', 'quantity_received', 'unit_cost', 'total',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
