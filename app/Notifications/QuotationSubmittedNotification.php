<?php

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationSubmittedNotification extends Notification
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
            'type'    => 'quotation_submitted',
            'title'   => 'Quotation Awaiting Approval',
            'body'    => "Quotation {$this->quotation->quotation_number} for {$this->quotation->client_name} (KES " . number_format($this->quotation->total, 0) . ") needs your approval.",
            'url'     => '/admin/quotations/' . $this->quotation->id . '/edit',
            'icon'    => '📄',
            'color'   => 'warning',
            'meta'    => [
                'quotation_id'     => $this->quotation->id,
                'quotation_number' => $this->quotation->quotation_number,
                'client'           => $this->quotation->client_name,
                'total'            => $this->quotation->total,
            ],
        ];
    }
}