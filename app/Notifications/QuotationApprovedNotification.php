<?php

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Quotation $quotation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'  => 'quotation_approved',
            'title' => 'Quotation Approved ✓',
            'body'  => "Your quotation {$this->quotation->quotation_number} for {$this->quotation->client_name} has been approved and is ready to send.",
            'url'   => '/admin/quotations/' . $this->quotation->id . '/edit',
            'icon'  => '✅',
            'color' => 'success',
            'meta'  => [
                'quotation_id'     => $this->quotation->id,
                'quotation_number' => $this->quotation->quotation_number,
                'client'           => $this->quotation->client_name,
            ],
        ];
    }
}