<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'customer_id', 'sale_id', 'quotation_id', 'created_by',
        'client_name', 'client_phone', 'client_email', 'client_address',
        'delivery_number', 'order_number', 'status', 'include_vat',
        'subtotal', 'discount_amount', 'vat_amount', 'total', 'amount_paid',
        'notes', 'footer_text', 'due_date', 'sent_at',
    ];

    protected $casts = [
        'include_vat'     => 'boolean',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'due_date'        => 'date',
        'sent_at'         => 'datetime',
    ];

    public static array $statuses = [
        'unpaid'    => 'Unpaid',
        'partial'   => 'Partial',
        'paid'      => 'Paid',
        'overdue'   => 'Overdue',
        'cancelled' => 'Cancelled',
    ];

    public static array $statusColors = [
        'unpaid'    => 'warning',
        'partial'   => 'info',
        'paid'      => 'success',
        'overdue'   => 'danger',
        'cancelled' => 'gray',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateNumber();
            }
        });

        static::saved(function (Invoice $invoice) {
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

    public static function generateNumber(): string
    {
        // Continues from 23477
        $seq = DB::table('document_sequences')->where('type', 'invoice')->first();
        $next = ($seq->last_number ?? 23477) + 1;
        DB::table('document_sequences')->where('type', 'invoice')
            ->update(['last_number' => $next, 'updated_at' => now()]);
        return "INV-{$next}";
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function balanceDue(): float
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function vatAmount(): float
    {
        return $this->include_vat
            ? round(($this->subtotal - $this->discount_amount) * 0.16, 2)
            : 0;
    }
}

// ────────────────────────────────────────────────────────────────────────────

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

        // After each item is saved, recalculate the parent invoice total
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