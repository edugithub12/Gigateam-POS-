<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransaction extends Model
{
    protected $fillable = [
        'merchant_request_id',
        'checkout_request_id',
        'phone',
        'amount',
        'reference',
        'description',
        'status',
        'mpesa_receipt',
        'result_code',
        'result_desc',
        'sale_id',
        'user_id',
        'completed_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'completed_at' => 'datetime',
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