<?php

namespace App\Mail;

use App\Models\TemporaryPermission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PermissionGrantedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TemporaryPermission $permission,
        public array $allPermissions = [],
        public mixed $expiresAt = null,
        public ?User $staffMember = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔑 New Access Granted — Gigateam POS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.permission-granted',
            with: [
                'permission'      => $this->permission,
                'allPermissions'  => $this->allPermissions,
                'expiresAt'       => $this->expiresAt,
                'staffMember'     => $this->staffMember ?? $this->permission->user,
            ],
        );
    }
}