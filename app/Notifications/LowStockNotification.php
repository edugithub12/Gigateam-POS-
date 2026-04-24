<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isOut = $this->product->stock_quantity <= 0;

        return [
            'type'  => 'low_stock',
            'title' => $isOut ? 'Product Out of Stock!' : 'Low Stock Alert',
            'body'  => $isOut
                ? "{$this->product->name} is out of stock. Please restock immediately."
                : "{$this->product->name} is running low — only {$this->product->stock_quantity} {$this->product->unit} remaining.",
            'url'   => '/admin/products/' . $this->product->id . '/edit',
            'icon'  => $isOut ? '🔴' : '🟠',
            'color' => $isOut ? 'danger' : 'warning',
            'meta'  => [
                'product_id'    => $this->product->id,
                'product_name'  => $this->product->name,
                'stock'         => $this->product->stock_quantity,
                'threshold'     => $this->product->low_stock_threshold,
            ],
        ];
    }
}