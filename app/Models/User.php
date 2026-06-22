<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'phone', 'specialization', 'technician_status',
        'id_number', 'technician_notes', 'is_active', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'          => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user')
                    ->withPivot(['shop_role', 'is_primary', 'is_active'])
                    ->withTimestamps();
    }

    public function activeLocations(): BelongsToMany
    {
        return $this->locations()->wherePivot('is_active', true);
    }

    // ── Location helpers ──────────────────────────────────────────────────────

    public function primaryLocation(): ?Location
    {
        if ($this->isSuperAdmin()) return null;

        return $this->activeLocations()
                    ->wherePivot('is_primary', true)
                    ->first()
            ?? $this->activeLocations()->first();
    }

    public function primaryLocationId(): ?int
    {
        return $this->primaryLocation()?->id;
    }

    public function shopRoleAt(?int $locationId): ?string
    {
        if (! $locationId) return null;

        return $this->activeLocations()
                    ->wherePivot('location_id', $locationId)
                    ->first()?->pivot->shop_role;
    }

    public function canAccessLocation(int $locationId): bool
    {
        if ($this->isSuperAdmin()) return true;

        return $this->activeLocations()
                    ->wherePivot('location_id', $locationId)
                    ->exists();
    }

    /**
     * True system-wide access: has the 'super_admin' role explicitly,
     * OR has the 'admin' role with NO shop assignments at all.
     *
     * This means an 'admin' who IS assigned to one or more shops is
     * treated as a shop-level admin (full access within their own
     * shop/shops), not a system-wide super admin. Only an admin with
     * zero shop assignments is assumed to be managing the whole
     * business and sees everything.
     */
    public function isSuperAdmin(): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        if ($this->hasRole('admin')) {
            return $this->locations()->count() === 0;
        }

        return false;
    }
}