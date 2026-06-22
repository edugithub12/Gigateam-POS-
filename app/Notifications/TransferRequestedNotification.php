<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\StockTransfer;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class TransferRequestedNotification extends Notification
{
    public function __construct(public StockTransfer $transfer) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        // donorProduct() is shop-scoped, and the donor product belongs to a
        // different shop than whichever shop is "active" for this request —
        // so the scoped relation resolves to null. Bypass the scope here.
        $donorProduct = Product::withoutShopScope()->find($this->transfer->donor_product_id);

        return FilamentNotification::make()
            ->title('Stock Transfer Request')
            ->body(
                "{$this->transfer->requestedBy->name} is requesting " .
                "{$this->transfer->quantity_requested} x " . ($donorProduct->name ?? 'Unknown product') . " " .
                "from {$this->transfer->fromLocation->name} " .
                "→ {$this->transfer->toLocation->name}"
            )
            ->warning()
            ->actions([
                Action::make('review')
                    ->label('Review Request')
                    ->url("/admin/stock-transfers/{$this->transfer->id}/edit")
                    ->button(),
            ])
            ->getDatabaseMessage();
    }
}