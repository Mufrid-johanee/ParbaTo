<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        return (int) $notification->notifiable_id === (int) $user->id
            && $notification->notifiable_type === User::class;
    }

    public function markRead(User $user, DatabaseNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
