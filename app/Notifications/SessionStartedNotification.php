<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionStartedNotification extends Notification
{
    use Queueable;

    public function __construct(public ClassSession $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'ClassTwin session started',
            'body' => $this->session->classroom->name.' is live.',
            'link' => route('classtwin.show', $this->session),
            'type' => 'session_started',
            'related_id' => $this->session->id,
            'session_id' => $this->session->id,
        ];
    }
}
