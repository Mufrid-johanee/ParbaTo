<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BadgeEarnedNotification extends Notification
{
    use Queueable;

    public function __construct(public Achievement $badge) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Badge earned: '.$this->badge->name,
            'body' => $this->badge->description ?? 'You unlocked a new ParbaTo badge.',
            'link' => route('student.profile'),
            'type' => 'badge_earned',
            'related_id' => $this->badge->id,
            'badge_id' => $this->badge->id,
            'slug' => $this->badge->slug,
        ];
    }
}
