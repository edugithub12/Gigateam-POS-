<?php

namespace App\Filament\Resources\JobCardResource\Pages;

use App\Filament\Resources\JobCardResource;
use Filament\Resources\Pages\EditRecord;

class EditJobCard extends EditRecord
{
    protected static string $resource = JobCardResource::class;

    // Store the original technician ID before saving
    protected ?int $originalTechnicianId = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capture the current technician before the record is updated
        $this->originalTechnicianId = $this->record->technician_id;
        return $data;
    }

    protected function afterSave(): void
    {
        // Send email only if technician was changed or newly assigned
        JobCardResource::sendTechnicianEmail($this->record, $this->originalTechnicianId);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}