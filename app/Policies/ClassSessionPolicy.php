<?php

namespace App\Policies;

use App\Models\ClassSession;
use App\Models\User;

class ClassSessionPolicy
{
    public function view(User $user, ClassSession $session): bool
    {
        $session->loadMissing('classroom');

        if ($session->classroom->isOwnedBy($user)) {
            return true;
        }

        return $session->classroom->hasMember($user);
    }

    public function start(User $user, ClassSession $session): bool
    {
        return false;
    }

    public function manage(User $user, ClassSession $session): bool
    {
        $session->loadMissing('classroom');

        return $session->classroom->isOwnedBy($user);
    }

    public function attend(User $user, ClassSession $session): bool
    {
        $session->loadMissing('classroom');

        return $session->classroom->hasMember($user);
    }

    public function end(User $user, ClassSession $session): bool
    {
        return $this->manage($user, $session);
    }
}
