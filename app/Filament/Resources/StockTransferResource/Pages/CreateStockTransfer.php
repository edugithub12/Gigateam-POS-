<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use App\Models\User;
use App\Notifications\TransferRequestedNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();
        return $data;
    }

    /**
     * Override the default record creation to self-heal from rare
     * transfer_number collisions (e.g. a previous failed/aborted attempt
     * having already consumed a number, or a double-submit slipping past
     * the disabled-button protection). On collision, drop the number and
     * let StockTransfer::creating() draw a fresh one, then retry.
     *
     * IMPORTANT: transfer_number must NEVER be pre-set here or in the form.
     * The creating() hook on StockTransfer is the sole place it is generated.
     * Pre-setting it here would cause DocumentSequence::next() to be called
     * during Filament's internal hydration/validation cycle (before the actual
     * insert), burning sequence numbers and causing the duplicate you see in
     * the query log (3x increments, 0 inserts).
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Strip any transfer_number that may have leaked in from the form state
        // or a previous retry — the creating() hook is the sole source of truth.
        unset($data['transfer_number']);

        $attempts = 0;

        while (true) {
            try {
                return static::getModel()::create($data);
            } catch (UniqueConstraintViolationException $e) {
                if (! str_contains($e->getMessage(), 'transfer_number') || ++$attempts >= 3) {
                    throw $e;
                }
                // The creating() hook will generate a fresh number on the next iteration.
                // No need to unset here again — it was never in $data to begin with.
            }
        }
    }

    protected function afterCreate(): void
    {
        $transfer = $this->record;

        // Notify: every true super admin (admin role, no shop assignments),
        // plus the shop_manager(s) of the DONOR shop (from_location_id) —
        // they're the ones who need to approve/dispatch this request.
        $superAdmins = User::role('admin')
            ->get()
            ->filter(fn (User $u) => $u->isSuperAdmin());

        // NOTE: wherePivot() only works when called directly on a
        // BelongsToMany relationship instance (e.g. $user->locations()),
        // not inside a whereHas() closure — there, $q is a plain query
        // builder over the pivot-joined table, so we filter the pivot's
        // own column (location_user.shop_role) with a normal where().
        $shopManagers = User::whereHas('activeLocations', function ($q) use ($transfer) {
            $q->where('locations.id', $transfer->from_location_id)
              ->where('location_user.shop_role', 'shop_manager');
        })->get();

        $notify = $superAdmins->merge($shopManagers)->unique('id');

        foreach ($notify as $user) {
            $user->notify(new TransferRequestedNotification($transfer));
        }
    }
}