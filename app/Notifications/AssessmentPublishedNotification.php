<?php

namespace App\Notifications;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Assessment $assessment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New assessment published',
            'body' => $this->assessment->title.' is ready for you.',
            'link' => route('student.assessments.show', $this->assessment),
            'type' => 'assessment_published',
            'related_id' => $this->assessment->id,
            'assessment_id' => $this->assessment->id,
        ];
    }
}
