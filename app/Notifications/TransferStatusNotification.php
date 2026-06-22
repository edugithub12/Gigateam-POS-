<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\StockTransfer;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TransferStatusNotification extends Notification
{
    public function __construct(public StockTransfer $transfer) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        // donorProduct() is shop-scoped, and the donor product usually
        // belongs to a different shop than whichever shop is "active" for
        // this request — so the scoped relation resolves to null. Bypass
        // the scope here, same as TransferRequestedNotification.
        $donorProduct = Product::withoutShopScope()->find($this->transfer->donor_product_id);

        $status  = $this->transfer->status;
        $product = $donorProduct->name ?? 'Unknown product';
        $qty     = $this->transfer->quantity_approved ?? $this->transfer->quantity_requested;

        [$title, $body, $type] = match ($status) {
            'approved'   => ["Transfer Approved ✓",  "Your request for {$qty} x {$product} has been approved and will be dispatched soon.", 'success'],
            'dispatched' => ["Stock Dispatched 🚚",   "{$qty} x {$product} has been dispatched from {$this->transfer->fromLocation->name}.", 'info'],
            'received'   => ["Stock Received ✓",      "{$qty} x {$product} has been received. Your stock has been updated.", 'success'],
            'rejected'   => ["Transfer Rejected ✗",   "Your request for {$product} was rejected. Reason: {$this->transfer->rejection_reason}", 'danger'],
            default      => ["Transfer Update",        "Transfer {$this->transfer->transfer_number} status: {$status}", 'info'],
        };

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->{$type}()
            ->getDatabaseMessage();
    }
}