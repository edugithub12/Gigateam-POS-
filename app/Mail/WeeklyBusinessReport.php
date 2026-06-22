<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyBusinessReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $data
    ) {}

    public function envelope(): Envelope
    {
        $weekStart = now()->startOfWeek()->format('d M');
        $weekEnd   = now()->endOfWeek()->format('d M Y');
        return new Envelope(
            subject: "📈 Weekly Business Report — {$weekStart} to {$weekEnd} | Gigateam POS",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-report',
        );
    }
}