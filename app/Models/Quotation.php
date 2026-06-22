<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Traits\LogsUserActivity;

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
    use LogsUserActivity;
    use SoftDeletes;
    use BelongsToShop; // provides location(), global shop scope, auto-fill location_id

    protected $fillable = [
        'quotation_number', 'customer_id', 'created_by', 'approved_by', 'location_id',
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
        // BelongsToShop::bootBelongsToShop() handles auto-filling location_id.
        static::creating(function (Quotation $quotation) {
            if (empty($quotation->quotation_number)) {
                $quotation->quotation_number = static::generateNumber();
            }
        });

        static::updated(function (Quotation $quotation) {
            if (! $quotation->wasChanged('status')) return;

            $newStatus = $quotation->status;
            $oldStatus = $quotation->getOriginal('status');

            // Salesperson submits → notify shop managers of THIS shop only
            if ($newStatus === 'pending_approval' && $oldStatus === 'draft') {
                $managers = User::whereHas('activeLocations', fn ($q) =>
                    $q->where('location_id', $quotation->location_id)
                      ->wherePivot('shop_role', 'shop_manager')
                )->get();

                // Also notify super admins / admins regardless of shop
                $admins = User::role('admin')->get();

                foreach ($managers->merge($admins)->unique('id') as $approver) {
                    $approver->notify(new QuotationSubmittedNotification($quotation));
                }
            }

            if ($newStatus === 'approved') {
                $creator = $quotation->createdBy;
                if ($creator && $creator->id !== auth()->id()) {
                    $creator->notify(new QuotationApprovedNotification($quotation));
                }
            }

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
        return DocumentSequence::next('quotation', activeShopId());
    }

    protected static function getActivityLogName(): string
    {
        return 'quotations';
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

    // location() relationship now provided by BelongsToShop trait

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

    // scopeForLocation() removed — BelongsToShop provides scopeForShop()
}
