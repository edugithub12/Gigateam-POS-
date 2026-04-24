<?php

namespace App\Notifications;

use App\Models\JobCard;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JobCardAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public JobCard $jobCard) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'  => 'job_assigned',
            'title' => 'New Job Card Assigned',
            'body'  => "You have been assigned job {$this->jobCard->job_number} — {$this->jobCard->category} at {$this->jobCard->site_address}."
                . ($this->jobCard->scheduled_date ? " Scheduled: {$this->jobCard->scheduled_date->format('d M Y')}" : ''),
            'url'   => '/admin/job-cards/' . $this->jobCard->id . '/edit',
            'icon'  => '🔧',
            'color' => 'info',
            'meta'  => [
                'job_card_id' => $this->jobCard->id,
                'job_number'  => $this->jobCard->job_number,
                'category'    => $this->jobCard->category,
                'site'        => $this->jobCard->site_address,
                'scheduled'   => $this->jobCard->scheduled_date?->format('d M Y'),
            ],
        ];
    }
}