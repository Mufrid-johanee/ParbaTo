<?php

namespace App\Notifications;

use App\Models\AssessmentAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentGradedNotification extends Notification
{
    use Queueable;

    public function __construct(public AssessmentAttempt $attempt) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Assessment graded',
            'body' => sprintf(
                '%s: %s%%',
                $this->attempt->assessment->title,
                number_format((float) $this->attempt->accuracy, 1)
            ),
            'link' => route('student.attempts.result', $this->attempt),
            'type' => 'assessment_graded',
            'related_id' => $this->attempt->id,
            'attempt_id' => $this->attempt->id,
            'assessment_id' => $this->attempt->assessment_id,
        ];
    }
}
