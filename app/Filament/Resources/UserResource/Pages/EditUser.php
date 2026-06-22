<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Load the user's existing pivot rows back into the repeater's
     * expected shape when the edit form opens.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['locationAssignments'] = $this->record->locations()
            ->get()
            ->map(fn ($loc) => [
                'location_id' => $loc->id,
                'shop_role'   => $loc->pivot->shop_role,
                'is_primary'  => (bool) $loc->pivot->is_primary,
            ])
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncLocationAssignments();
    }

    protected function syncLocationAssignments(): void
    {
        $assignments = $this->form->getRawState()['locationAssignments'] ?? [];

        $syncData = [];
        foreach ($assignments as $row) {
            if (empty($row['location_id'])) continue;

            $syncData[$row['location_id']] = [
                'shop_role'  => $row['shop_role'] ?? 'cashier',
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'is_active'  => true,
            ];
        }

        // sync() here will also REMOVE assignments that were deleted from
        // the repeater, which is the correct behavior for editing.
        $this->record->locations()->sync($syncData);
    }
}
