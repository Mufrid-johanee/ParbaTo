<?php

namespace App\Policies;

use App\Models\MissionEnrollment;
use App\Models\User;

class MissionEnrollmentPolicy
{
    public function view(User $user, MissionEnrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id
            || $user->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN);
    }

    public function completeTask(User $user, MissionEnrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id;
    }

    public function submit(User $user, MissionEnrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id;
    }

    public function evaluate(User $user, MissionEnrollment $enrollment): bool
    {
        return $user->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN);
    }
}
