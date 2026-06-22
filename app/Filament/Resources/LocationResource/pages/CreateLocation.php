<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    protected function afterCreate(): void
    {
        $location = $this->record;

        // NOTE: previously this seeded a LocationStock row (qty 0) for every
        // existing product, under the old shared-catalog model. Under the
        // per-shop catalog model, each shop owns its own independent Product
        // rows — a new shop simply starts with NO products at all, and gets
        // its opening stock via Stock Transfers from another shop, which
        // auto-creates matching Product rows on receipt
        // (see StockTransfer::moveStock()). No seeding needed here.

        // ── Seed per-shop document sequences ───────────────────────────────
        // Copy global sequences (location_id = null) into this new shop
        $globalSeqs = \App\Models\DocumentSequence::whereNull('location_id')->get();
        foreach ($globalSeqs as $seq) {
            \App\Models\DocumentSequence::firstOrCreate(
                [
                    'type'        => $seq->type,
                    'location_id' => $location->id,
                ],
                [
                    'prefix'      => $seq->prefix,
                    'last_number' => 0,
                    'padding'     => $seq->padding,
                ]
            );
        }

        // ── Switch the admin into the new shop automatically ──────────────────
        session(['active_shop_id' => $location->id]);
        app()->instance('active_shop', $location);

        Notification::make()
            ->success()
            ->title("Shop '{$location->name}' created")
            ->body("You are now viewing {$location->name}. Use Stock Transfers to add opening stock.")
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        // After creation, go to the dashboard scoped to the new shop
        return route('filament.admin.pages.dashboard');
    }
}