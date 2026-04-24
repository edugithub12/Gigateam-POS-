<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_number', 'customer_id', 'user_id', 'quotation_id',
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
        static::creating(function (Sale $sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = static::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $seq = DB::table('document_sequences')->where('type', 'sale')->first();
        $next = ($seq->last_number ?? 0) + 1;
        DB::table('document_sequences')->where('type', 'sale')
            ->update(['last_number' => $next, 'updated_at' => now()]);
        $year  = now()->format('Y');
        $month = now()->format('m');
        return "SAL-{$year}{$month}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
}

// ────────────────────────────────────────────────────────────────────────────

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'product_name', 'product_sku', 'unit',
        'unit_price', 'cost_price', 'quantity', 'discount', 'total',
        'needs_installation',
    ];

    protected $casts = [
        'unit_price'          => 'decimal:2',
        'cost_price'          => 'decimal:2',
        'discount'            => 'decimal:2',
        'total'               => 'decimal:2',
        'needs_installation'  => 'boolean',
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