<?php

namespace App\Notifications;

use App\Models\MissionEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MissionEvaluationCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public MissionEnrollment $enrollment, public float $score) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Mission evaluated',
            'body' => sprintf('%s scored %s%%', $this->enrollment->mission->title, number_format($this->score, 1)),
            'link' => route('learnquest.show', $this->enrollment->mission),
            'type' => 'mission_evaluated',
            'related_id' => $this->enrollment->id,
            'enrollment_id' => $this->enrollment->id,
        ];
    }
}
