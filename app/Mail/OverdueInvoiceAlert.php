<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class OverdueInvoiceAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $invoices
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->invoices->count();
        return new Envelope(
            subject: "🔴 {$count} Overdue Invoice(s) — " . now()->format('d M Y') . ' | Gigateam POS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.overdue-invoices',
        );
    }
}