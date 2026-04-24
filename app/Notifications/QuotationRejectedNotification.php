<?php

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Quotation $quotation,
        public string $reason = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $body = "Your quotation {$this->quotation->quotation_number} for {$this->quotation->client_name} was not approved.";
        if ($this->reason) {
            $body .= " Reason: {$this->reason}";
        }

        return [
            'type'  => 'quotation_rejected',
            'title' => 'Quotation Not Approved',
            'body'  => $body,
            'url'   => '/admin/quotations/' . $this->quotation->id . '/edit',
            'icon'  => '❌',
            'color' => 'danger',
            'meta'  => [
                'quotation_id'     => $this->quotation->id,
                'quotation_number' => $this->quotation->quotation_number,
                'client'           => $this->quotation->client_name,
                'reason'           => $this->reason,
            ],
        ];
    }
}