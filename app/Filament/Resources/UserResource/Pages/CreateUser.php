<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $this->syncLocationAssignments();
    }

    /**
     * The Repeater field 'locationAssignments' is dehydrated(false) on the
     * form (it doesn't map to a column on `users`), so Filament never saves
     * it automatically. We read its raw state here and manually sync it
     * into the location_user pivot table.
     */
    protected function syncLocationAssignments(): void
    {
        $assignments = $this->form->getRawState()['locationAssignments'] ?? [];

        if (empty($assignments)) {
            return;
        }

        $syncData = [];
        foreach ($assignments as $row) {
            if (empty($row['location_id'])) continue;

            $syncData[$row['location_id']] = [
                'shop_role'  => $row['shop_role'] ?? 'cashier',
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'is_active'  => true,
            ];
        }

        if (! empty($syncData)) {
            $this->record->locations()->sync($syncData);
        }
    }
}
