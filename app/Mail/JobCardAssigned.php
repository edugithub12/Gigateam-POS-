<?php

namespace App\Mail;

use App\Models\JobCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobCardAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobCard $jobCard
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔧 New Job Assigned: ' . $this->jobCard->reference . ' — ' . ($this->jobCard->customer->name ?? 'Client') . ' | Gigateam',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-card-assigned',
        );
    }
}