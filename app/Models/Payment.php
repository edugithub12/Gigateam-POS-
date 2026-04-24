<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'sale_id', 'amount', 'method', 'reference', 'status', 'notes', 'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static array $methods = [
        'cash'          => 'Cash',
        'mpesa'         => 'M-Pesa',
        'bank_transfer' => 'Bank Transfer',
        'card'          => 'Card',
        'cheque'        => 'Cheque',
        'credit'        => 'Credit',
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