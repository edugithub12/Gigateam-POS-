<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'company_name', 'contact_person',
        'phone', 'phone_alt', 'email', 'id_number',
        'address', 'area', 'city',
        'credit_limit', 'outstanding_balance', 'notes', 'is_active',
    ];

    protected $casts = [
        'credit_limit'        => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_active'           => 'boolean',
    ];

    public static array $types = [
        'individual' => 'Individual',
        'business'   => 'Business',
        'government' => 'Government',
        'school'     => 'School / Institution',
        'estate'     => 'Estate / Compound',
        'ngo'        => 'NGO / Church',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getDisplayNameAttribute(): string
    {
        if ($this->type !== 'individual' && $this->company_name) {
            return $this->company_name;
        }
        return $this->name;
    }

    public function totalPurchases(): float
    {
        return $this->sales()->where('payment_status', 'paid')->sum('total');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}