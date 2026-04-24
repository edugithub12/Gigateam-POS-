<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class DeliveryNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'delivery_number', 'type', 'customer_id', 'technician_id',
        'sale_id', 'invoice_id', 'job_card_id', 'created_by',
        'recipient_name', 'recipient_phone', 'delivery_address', 'site_location',
        'status', 'notes', 'footer_text', 'delivery_date',
        'dispatched_at', 'delivered_at',
    ];

    protected $casts = [
        'delivery_date'  => 'date',
        'dispatched_at'  => 'datetime',
        'delivered_at'   => 'datetime',
    ];

    public static array $types = [
        'customer'   => 'Customer Delivery',
        'technician' => 'Technician Site Delivery',
    ];

    public static array $statuses = [
        'pending'    => 'Pending',
        'dispatched' => 'Dispatched',
        'delivered'  => 'Delivered',
        'returned'   => 'Returned',
    ];

    public static array $statusColors = [
        'pending'    => 'gray',
        'dispatched' => 'warning',
        'delivered'  => 'success',
        'returned'   => 'danger',
    ];

    protected static function booted(): void
    {
        static::creating(function (DeliveryNote $dn) {
            if (empty($dn->delivery_number)) {
                $dn->delivery_number = static::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $seq = DB::table('document_sequences')->where('type', 'delivery_note')->first();
        $next = ($seq->last_number ?? 0) + 1;
        DB::table('document_sequences')->where('type', 'delivery_note')
            ->update(['last_number' => $next, 'updated_at' => now()]);
        $year  = now()->format('Y');
        $month = now()->format('m');
        return "DN-{$year}{$month}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class)->orderBy('sort_order');
    }
}

// ────────────────────────────────────────────────────────────────────────────

class DeliveryNoteItem extends Model
{
    protected $fillable = [
        'delivery_note_id', 'product_id', 'sort_order',
        'description', 'unit', 'quantity', 'notes',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}