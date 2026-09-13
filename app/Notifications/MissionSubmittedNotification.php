<?php

namespace App\Notifications;

use App\Models\MissionEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MissionSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public MissionEnrollment $enrollment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Mission submitted',
            'body' => sprintf(
                '%s submitted %s',
                $this->enrollment->user->preferredName(),
                $this->enrollment->mission->title
            ),
            'link' => route('learnquest.evaluations.show', $this->enrollment),
            'type' => 'mission_submitted',
            'related_id' => $this->enrollment->id,
            'enrollment_id' => $this->enrollment->id,
        ];
    }
}
