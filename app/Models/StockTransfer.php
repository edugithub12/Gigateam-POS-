<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number', 'donor_product_id', 'receiver_product_id',
        'product_was_auto_created',
        'from_location_id', 'to_location_id',
        'quantity_requested', 'quantity_approved',
        'status', 'notes', 'rejection_reason',
        'requested_by', 'approved_by', 'received_by',
        'approved_at', 'dispatched_at', 'received_at',
        'is_auto_suggested', 'urgency_level', 'trigger_event',
        'requesting_shop_stock_at_request', 'donor_shop_stock_at_request',
    ];

    protected $casts = [
        'approved_at'               => 'datetime',
        'dispatched_at'             => 'datetime',
        'received_at'               => 'datetime',
        'product_was_auto_created'  => 'boolean',
        'is_auto_suggested'         => 'boolean',
    ];

    public static array $statuses = [
        'pending'    => 'Pending',
        'approved'   => 'Approved',
        'dispatched' => 'Dispatched',
        'received'   => 'Received',
        'cancelled'  => 'Cancelled',
        'rejected'   => 'Rejected',
    ];

    public static array $statusColors = [
        'pending'    => 'warning',
        'approved'   => 'info',
        'dispatched' => 'primary',
        'received'   => 'success',
        'cancelled'  => 'gray',
        'rejected'   => 'danger',
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (StockTransfer $transfer) {
            // Guard: only generate if not already set. This prevents the
            // number from being burned multiple times if Eloquent's creating
            // hook fires more than once (e.g. from a retry loop in
            // handleRecordCreation). The actual DB::transaction + lockForUpdate
            // inside generateNumber() ensures no two concurrent requests can
            // ever receive the same sequence number.
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = static::generateNumber();
            }
        });

        static::updating(function (StockTransfer $transfer) {
            // Ensure received_by is set BEFORE moveStock() runs, regardless
            // of whether the form explicitly submitted that field. This
            // guarantees stock_movements.user_id (NOT NULL) always has a
            // value, even if the receive action only toggled `status`.
            if ($transfer->status === 'received' && empty($transfer->received_by)) {
                $transfer->received_by = auth()->id();
            }
        });

        static::updated(function (StockTransfer $transfer) {
            if ($transfer->wasChanged('status') && $transfer->status === 'received') {
                static::moveStock($transfer);
            }
        });
    }

    /**
     * Generate the next transfer number for the active shop.
     *
     * Wrapped in a DB transaction with a FOR UPDATE lock on the sequence row
     * so that concurrent requests cannot receive the same number. Without the
     * transaction wrapper here, the lock acquired inside DocumentSequence::next()
     * is released immediately after that query, meaning a second connection can
     * acquire its own lock before the first has finished incrementing — resulting
     * in both connections reading the same `last_number` and generating a
     * duplicate transfer_number.
     */
    public static function generateNumber(): string
    {
        return DB::transaction(function () {
            return DocumentSequence::next('transfer', activeShopId());
        });
    }

    // ── Stock movement on received ───────────────────────────────────────────

    /**
     * Moves stock from the donor's product row to the receiver's.
     * If the receiver has no matching product (by barcode/SKU), one is
     * auto-created by copying the donor's catalog details, per the
     * per-shop-catalog model: each shop owns its own product rows.
     */
    protected static function moveStock(StockTransfer $transfer): void
    {
        $qty = $transfer->quantity_approved ?? $transfer->quantity_requested;

        // Fallback chain so this NEVER inserts a null user_id, even if
        // received_by somehow still isn't set (e.g. triggered from a
        // console command or queued job with no authenticated user).
        $actingUserId = $transfer->received_by ?? auth()->id() ?? $transfer->requested_by;

        DB::transaction(function () use ($transfer, $qty, $actingUserId) {

            // ── Deduct from the donor's product row ────────────────────────────
            $donorProduct = Product::withoutShopScope()
                ->lockForUpdate()
                ->findOrFail($transfer->donor_product_id);

            $donorBefore = $donorProduct->quantity;
            $donorProduct->decrement('quantity', $qty);

            StockMovement::create([
                'product_id'   => $donorProduct->id,
                'location_id'  => $transfer->from_location_id,
                'type'         => 'transfer_out',
                'source'       => 'stock_transfer',
                'source_id'    => $transfer->id,
                'quantity'     => -$qty,
                'stock_before' => $donorBefore,
                'stock_after'  => $donorBefore - $qty,
                'notes'        => "Transferred to {$transfer->toLocation->name} — {$transfer->transfer_number}",
                'user_id'      => $actingUserId,
            ]);

            // ── Find or create the receiver's matching product row ─────────────
            $receiverProduct = Product::withoutShopScope()
                ->where('location_id', $transfer->to_location_id)
                ->where(function ($q) use ($donorProduct) {
                    if ($donorProduct->barcode) {
                        $q->orWhere('barcode', $donorProduct->barcode);
                    }
                    $q->orWhere('sku', $donorProduct->sku);
                })
                ->lockForUpdate()
                ->first();

            $wasAutoCreated = false;

            if (! $receiverProduct) {
                $receiverProduct = Product::withoutShopScope()->create([
                    'location_id'         => $transfer->to_location_id,
                    'category_id'         => $donorProduct->category_id,
                    'name'                => $donorProduct->name,
                    'sku'                 => $donorProduct->sku,
                    'description'         => $donorProduct->description,
                    'brand'               => $donorProduct->brand,
                    'model_number'        => $donorProduct->model_number,
                    'unit'                => $donorProduct->unit,
                    'cost_price'          => $donorProduct->cost_price,
                    'selling_price'       => $donorProduct->selling_price,
                    'installation_price'  => $donorProduct->installation_price,
                    'quantity'            => 0,
                    'reorder_point'       => $donorProduct->reorder_point,
                    'is_service'          => false,
                    'is_active'           => true,
                    'barcode'             => $donorProduct->barcode,
                ]);

                $wasAutoCreated = true;
            }

            $receiverBefore = $receiverProduct->quantity;
            $receiverProduct->increment('quantity', $qty);

            StockMovement::create([
                'product_id'   => $receiverProduct->id,
                'location_id'  => $transfer->to_location_id,
                'type'         => 'transfer_in',
                'source'       => 'stock_transfer',
                'source_id'    => $transfer->id,
                'quantity'     => $qty,
                'stock_before' => $receiverBefore,
                'stock_after'  => $receiverBefore + $qty,
                'notes'        => "Received from {$transfer->fromLocation->name} — {$transfer->transfer_number}",
                'user_id'      => $actingUserId,
            ]);

            $transfer->withoutEvents(fn () => $transfer->updateQuietly([
                'receiver_product_id'      => $receiverProduct->id,
                'product_was_auto_created' => $wasAutoCreated,
            ]));
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function donorProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'donor_product_id');
    }

    public function receiverProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'receiver_product_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ── Permission helpers ────────────────────────────────────────────────────

    public function canBeApprovedBy(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->shopRoleAt($this->from_location_id) === 'shop_manager';
    }

    public function canBeReceivedBy(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->canAccessLocation($this->to_location_id);
    }
}