<?php

namespace App\Models;

use App\Notifications\QuotationApprovedNotification;
use App\Notifications\QuotationRejectedNotification;
use App\Notifications\QuotationSubmittedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quotation_number', 'customer_id', 'created_by', 'approved_by',
        'client_name', 'client_phone', 'client_email', 'client_address',
        'site_location', 'status', 'notes', 'terms', 'footer_text',
        'include_vat', 'subtotal', 'discount_amount', 'vat_amount', 'total',
        'valid_until', 'submitted_at', 'approved_at', 'sent_at', 'converted_at',
    ];

    protected $casts = [
        'include_vat'     => 'boolean',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'valid_until'     => 'date',
        'submitted_at'    => 'datetime',
        'approved_at'     => 'datetime',
        'sent_at'         => 'datetime',
        'converted_at'    => 'datetime',
    ];

    public static array $statuses = [
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved'         => 'Approved',
        'sent'             => 'Sent to Client',
        'accepted'         => 'Accepted',
        'rejected'         => 'Rejected',
        'converted'        => 'Converted',
    ];

    public static array $statusColors = [
        'draft'            => 'gray',
        'pending_approval' => 'warning',
        'approved'         => 'info',
        'sent'             => 'primary',
        'accepted'         => 'success',
        'rejected'         => 'danger',
        'converted'        => 'success',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation) {
            if (empty($quotation->quotation_number)) {
                $quotation->quotation_number = static::generateNumber();
            }
        });

        static::updated(function (Quotation $quotation) {
            if (!$quotation->wasChanged('status')) return;

            $newStatus = $quotation->status;
            $oldStatus = $quotation->getOriginal('status');

            // Salesperson submits → notify all admins
            if ($newStatus === 'pending_approval' && $oldStatus === 'draft') {
                $admins = User::role('admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new QuotationSubmittedNotification($quotation));
                }
            }

            // Admin approves → notify the creator
            if ($newStatus === 'approved') {
                $creator = $quotation->createdBy;
                if ($creator && $creator->id !== auth()->id()) {
                    $creator->notify(new QuotationApprovedNotification($quotation));
                }
            }

            // Admin rejects → notify the creator
            if ($newStatus === 'rejected') {
                $creator = $quotation->createdBy;
                if ($creator && $creator->id !== auth()->id()) {
                    $creator->notify(new QuotationRejectedNotification($quotation));
                }
            }
        });
    }

    public static function generateNumber(): string
    {
        $seq = DB::table('document_sequences')->where('type', 'quotation')->first();
        $next = ($seq->last_number ?? 0) + 1;
        DB::table('document_sequences')->where('type', 'quotation')
            ->update(['last_number' => $next, 'updated_at' => now()]);
        $year  = now()->format('Y');
        $month = now()->format('m');
        return "QT-{$year}{$month}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('total');
        $taxable  = $subtotal - $this->discount_amount;
        $vat      = $this->include_vat ? round($taxable * 0.16, 2) : 0;

        $this->update([
            'subtotal'   => $subtotal,
            'vat_amount' => $vat,
            'total'      => $taxable + $vat,
        ]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }
}