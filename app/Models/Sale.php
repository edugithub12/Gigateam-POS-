<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Traits\LogsUserActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use LogsUserActivity;
    use SoftDeletes;
    use BelongsToShop; // provides location(), global shop scope, auto-fill location_id

    protected $fillable = [
        'sale_number', 'customer_id', 'user_id', 'location_id', 'quotation_id',
        'subtotal', 'discount_amount', 'vat_amount', 'total',
        'amount_paid', 'change_given', 'payment_status', 'sale_type',
        'include_vat', 'notes', 'footer_text',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'change_given'    => 'decimal:2',
        'include_vat'     => 'boolean',
    ];

    public static array $saleTypes = [
        'walk_in'              => 'Walk In',
        'phone_order'          => 'Phone Order',
        'quotation_conversion' => 'From Quotation',
        'whatsapp'             => 'WhatsApp Order',
    ];

    public static array $paymentStatuses = [
        'unpaid'  => 'Unpaid',
        'partial' => 'Partial',
        'paid'    => 'Paid',
    ];

    protected static function booted(): void
    {
        // NOTE: BelongsToShop::bootBelongsToShop() already handles
        // auto-filling location_id from activeShopId() on creation.
        // We only handle sale_number generation here.
        static::creating(function (Sale $sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = static::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        return DocumentSequence::next('sale', activeShopId());
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Sale {$eventName}")
            ->useLogName('sales');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // location() relationship now provided by BelongsToShop trait

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoice(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function balanceDue(): float
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function profit(): float
    {
        return $this->items->sum(fn ($item) =>
            ($item->unit_price - $item->cost_price) * $item->quantity
        );
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    // scopeForLocation() removed — BelongsToShop provides scopeForShop()
    // which does the same thing but also bypasses the global scope correctly.
    // Old calls to ->forLocation($id) should be updated to ->forShop($id).
}

// ────────────────────────────────────────────────────────────────────────────

class SaleItem extends Model
{
    use LogsUserActivity;
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

// ────────────────────────────────────────────────────────────────────────────

class Payment extends Model
{
    use LogsUserActivity;
    protected $fillable = [
        'sale_id', 'amount', 'method', 'reference', 'status', 'notes', 'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static array $methods = [
        'cash'          => 'Cash',
        'mpesa'         => 'M-Pesa',
        'bank_transfer' => 'Bank Transfer',
        'card'          => 'Card',
        'cheque'        => 'Cheque',
        'credit'        => 'Credit',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
