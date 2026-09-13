<?php

namespace App\Notifications;

use App\Models\AssessmentAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentNeedsReviewNotification extends Notification
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
            'title' => 'Short-answer review needed',
            'body' => sprintf(
                '%s submitted %s',
                $this->attempt->user->preferredName(),
                $this->attempt->assessment->title
            ),
            'link' => route('teacher.assessments.attempts.show', [$this->attempt->assessment, $this->attempt]),
            'type' => 'assessment_needs_review',
            'related_id' => $this->attempt->id,
            'attempt_id' => $this->attempt->id,
            'assessment_id' => $this->attempt->assessment_id,
        ];
    }
}
