<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Technician extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'phone', 'phone_alt', 'email',
        'id_number', 'specialization', 'status', 'notes',
    ];

    public static array $specializations = [
        'CCTV'           => 'CCTV',
        'Electric Fencing' => 'Electric Fencing',
        'Biometric'      => 'Biometric',
        'Alarm Systems'  => 'Alarm Systems',
        'Access Control' => 'Access Control',
        'Networking'     => 'Networking',
        'General'        => 'General',
    ];

    public static array $statuses = [
        'active'   => 'Active',
        'inactive' => 'Inactive',
        'on_leave' => 'On Leave',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function activeJobs(): HasMany
    {
        return $this->hasMany(JobCard::class)
                    ->whereIn('status', ['scheduled', 'in_progress']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}