<?php

namespace App\Notifications;

use App\Models\Recommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FlexLearnRecommendationUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Recommendation $recommendation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'FlexLearn path updated',
            'body' => $this->recommendation->title,
            'link' => route('flexlearn.index'),
            'type' => 'flexlearn_recommendation',
            'related_id' => $this->recommendation->id,
            'recommendation_id' => $this->recommendation->id,
            'rule_key' => $this->recommendation->rule_key,
        ];
    }
}
