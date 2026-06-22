<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        // Stock lives directly on the (shop-scoped) Product row now —
        // no separate LocationStock lookup needed.
        $qty       = $this->product->quantity;
        $threshold = $this->product->reorder_point;
        $shopName  = $this->product->location?->name ?? 'Unknown Shop';
        $isOut     = $qty <= 0;

        return [
            'type'  => 'low_stock',
            'title' => $isOut ? 'Out of Stock!' : 'Low Stock Alert',
            'body'  => $isOut
                ? "{$this->product->name} is out of stock at {$shopName}. Please arrange a transfer or restock."
                : "{$this->product->name} is running low at {$shopName} — only {$qty} {$this->product->unit} remaining (reorder at {$threshold}).",
            'url'   => '/admin/products/' . $this->product->id . '/edit',
            'icon'  => $isOut ? 'danger' : 'warning',
            'color' => $isOut ? 'danger' : 'warning',
            'meta'  => [
                'product_id'    => $this->product->id,
                'product_name'  => $this->product->name,
                'location_id'   => $this->product->location_id,
                'location_name' => $shopName,
                'stock'         => $qty,
                'threshold'     => $threshold,
            ],
        ];
    }
}