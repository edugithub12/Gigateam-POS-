<?php

namespace App\Models;

use App\Notifications\JobCardAssignedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class JobCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'job_number', 'customer_id', 'technician_id', 'sale_id',
        'quotation_id', 'invoice_id', 'created_by',
        'client_name', 'client_phone', 'site_address', 'site_area',
        'job_type', 'category', 'work_description', 'work_done', 'status',
        'scheduled_date', 'scheduled_time', 'started_at', 'completed_at',
        'labour_cost', 'transport_cost',
        'technician_notes', 'client_signature', 'client_satisfied',
        'follow_up_notes', 'follow_up_date',
    ];

    protected $casts = [
        'labour_cost'      => 'decimal:2',
        'transport_cost'   => 'decimal:2',
        'scheduled_date'   => 'date',
        'follow_up_date'   => 'date',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
        'client_satisfied' => 'boolean',
    ];

    public static array $statuses = [
        'pending'     => 'Pending',
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'invoiced'    => 'Invoiced',
        'cancelled'   => 'Cancelled',
    ];

    public static array $statusColors = [
        'pending'     => 'gray',
        'scheduled'   => 'info',
        'in_progress' => 'warning',
        'completed'   => 'success',
        'invoiced'    => 'success',
        'cancelled'   => 'danger',
    ];

    public static array $jobTypes = [
        'installation'  => 'Installation',
        'maintenance'   => 'Maintenance',
        'repair'        => 'Repair',
        'survey'        => 'Site Survey',
        'upgrade'       => 'Upgrade',
        'commissioning' => 'Commissioning',
    ];

    public static array $categories = [
        'CCTV'             => 'CCTV',
        'Electric Fencing' => 'Electric Fencing',
        'Biometric'        => 'Biometric',
        'Alarm Systems'    => 'Alarm Systems',
        'Access Control'   => 'Access Control',
        'Networking'       => 'Networking',
        'General'          => 'General',
    ];

    protected static function booted(): void
    {
        static::creating(function (JobCard $job) {
            if (empty($job->job_number)) {
                $job->job_number = static::generateNumber();
            }
        });

        // Notify technician when assigned or reassigned
        static::updated(function (JobCard $job) {
            if ($job->wasChanged('technician_id') && $job->technician_id) {
                $technician = $job->technician;
                // Notify the linked user account if exists
                if ($technician && $technician->user_id) {
                    $user = User::find($technician->user_id);
                    $user?->notify(new JobCardAssignedNotification($job));
                }
            }
        });

        static::created(function (JobCard $job) {
            if ($job->technician_id) {
                $technician = $job->technician;
                if ($technician && $technician->user_id) {
                    $user = User::find($technician->user_id);
                    $user?->notify(new JobCardAssignedNotification($job));
                }
            }
        });
    }

    public static function generateNumber(): string
    {
        $seq = DB::table('document_sequences')->where('type', 'job_card')->first();
        $next = ($seq->last_number ?? 0) + 1;
        DB::table('document_sequences')->where('type', 'job_card')
            ->update(['last_number' => $next, 'updated_at' => now()]);
        $year  = now()->format('Y');
        $month = now()->format('m');
        return "JOB-{$year}{$month}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(JobCardItem::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class, 'job_card_id');
    }

    public function totalMaterialsCost(): float
    {
        return $this->items->sum('total');
    }

    public function grandTotal(): float
    {
        return $this->totalMaterialsCost() + $this->labour_cost + $this->transport_cost;
    }
}