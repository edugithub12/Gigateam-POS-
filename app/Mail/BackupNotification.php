<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public bool   $success,
        public string $filename = '',
        public string $filesize = '',
        public string $errorMessage = '',
        public bool   $usbCopied = false,
        public bool   $driveSynced = false,
    ) {}

    public function envelope(): Envelope
    {
        $icon    = $this->success ? '✅' : '🚨';
        $status  = $this->success ? 'Backup Successful' : 'BACKUP FAILED';
        return new Envelope(
            subject: "{$icon} {$status} — " . now()->format('d M Y') . ' | Gigateam POS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.backup-notification',
        );
    }
}