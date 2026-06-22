<?php

namespace App\Models;

use App\Traits\LogsUserActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TemporaryPermission extends Model
{
    use LogsUserActivity;
    protected $fillable = [
        'user_id',
        'permission',
        'scope_model',
        'scope_id',
        'granted_by',
        'reason',
        'granted_at',
        'expires_at',
        'status',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    // Only active grants that haven't expired
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    // Only expired grants
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'active')
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
              });
        });
    }

    // For a specific user
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // For a specific permission
    public function scopeForPermission(Builder $query, string $permission): Builder
    {
        return $query->where('permission', $permission);
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getIsRevokedAttribute(): bool
    {
        return $this->status === 'revoked';
    }

    public function getTimeRemainingAttribute(): string
    {
        if ($this->status === 'revoked') return 'Revoked';
        if ($this->expires_at === null)  return 'No expiry';
        if ($this->expires_at->isPast()) return 'Expired';

        $diff = now()->diff($this->expires_at);

        if ($diff->days > 0)  return $diff->days . 'd ' . $diff->h . 'h remaining';
        if ($diff->h > 0)     return $diff->h . 'h ' . $diff->i . 'm remaining';
        return $diff->i . ' minutes remaining';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match(true) {
            $this->status === 'revoked'           => 'danger',
            $this->is_expired                     => 'gray',
            $this->expires_at === null            => 'success',
            $this->expires_at->diffInHours() < 2 => 'warning',
            default                               => 'success',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'revoked')  return 'Revoked';
        if ($this->is_expired)            return 'Expired';
        if ($this->expires_at === null)   return 'Permanent';
        return 'Active — ' . $this->time_remaining;
    }

    // ── Actions ────────────────────────────────────────────────────────────────

    public function revoke(int $revokedBy, string $reason = ''): void
    {
        $this->update([
            'status'             => 'revoked',
            'revoked_at'         => now(),
            'revoked_by'         => $revokedBy,
            'revocation_reason'  => $reason,
        ]);
    }

    // ── Static helpers ─────────────────────────────────────────────────────────

    /**
     * Check if a user has an active temporary permission
     */
    public static function userHas(int $userId, string $permission): bool
    {
        return static::active()
                     ->forUser($userId)
                     ->forPermission($permission)
                     ->exists();
    }

    /**
     * Grant a temporary permission to a user
     */
    public static function grant(
        int     $userId,
        string  $permission,
        int     $grantedBy,
        ?Carbon $expiresAt = null,
        string  $reason    = '',
    ): static {
        return static::create([
            'user_id'    => $userId,
            'permission' => $permission,
            'granted_by' => $grantedBy,
            'expires_at' => $expiresAt,
            'reason'     => $reason,
            'granted_at' => now(),
            'status'     => 'active',
        ]);
    }

    /**
     * Mark all expired grants as expired (run by scheduler)
     */
    public static function expireOverdue(): int
    {
        return static::where('status', 'active')
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now())
                     ->update(['status' => 'expired']);
    }
}