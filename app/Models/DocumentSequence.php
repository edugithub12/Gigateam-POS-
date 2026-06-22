<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DocumentSequence extends Model
{
    protected $fillable = [
        'location_id', 'type', 'prefix', 'last_number', 'padding',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Generate the next document number for a given type and location.
     * Uses a DB transaction with pessimistic locking to prevent duplicates.
     */
    public static function next(string $type, ?int $locationId = null): string
    {
        return DB::transaction(function () use ($type, $locationId) {
            $seq = static::where('type', $type)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                // Fall back to global sequence if no shop-specific one exists
                $seq = static::where('type', $type)
                    ->whereNull('location_id')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $seq) {
                throw new \RuntimeException("No document sequence found for type: {$type}");
            }

            // Self-heal: this counter is a separate row from the table it
            // numbers, so it can drift behind reality whenever a number
            // gets consumed but the record that was meant to use it never
            // lands (failed inserts, rolled-back transactions, aborted
            // requests during testing, etc). Before handing out the next
            // number, make sure we're not about to reissue one that's
            // already taken — jump the counter forward to match the real
            // max in the target table first.
            $maxExisting = static::maxExistingNumber($type, $locationId);

            if ($maxExisting !== null && $maxExisting > $seq->last_number) {
                $seq->last_number = $maxExisting;
            }

            $seq->increment('last_number');
            $seq->refresh();

            return $seq->prefix . str_pad($seq->last_number, $seq->padding, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Look up the highest number currently in use for this document type,
     * so next() can detect and correct counter drift. Add a `match($type)`
     * arm here for each document type that uses sequences (transfers,
     * invoices, etc), pointing at the right table/column/location field.
     */
    protected static function maxExistingNumber(string $type, ?int $locationId): ?int
    {
        return match ($type) {
            'transfer' => StockTransfer::where('to_location_id', $locationId)
                ->lockForUpdate()
                ->max(DB::raw("CAST(SUBSTRING_INDEX(transfer_number, '-', -1) AS UNSIGNED)")),
            default => null,
        };
    }
}