<?php

namespace App\Filament\Resources\TemporaryPermissionResource\Pages;

use App\Filament\Resources\TemporaryPermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemporaryPermissions extends ListRecords
{
    protected static string $resource = TemporaryPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Grant Permission')
                ->icon('heroicon-o-plus'),
        ];
    }
}