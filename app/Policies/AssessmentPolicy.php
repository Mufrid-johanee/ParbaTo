<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($assessment->isOwnedBy($user)) {
            return true;
        }

        if ($user->isStudent() && $assessment->isPublished()) {
            return app(\App\Services\AssessmentService::class)->studentCanAccess($user, $assessment);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $assessment->isOwnedBy($user);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $assessment->isOwnedBy($user);
    }

    public function publish(User $user, Assessment $assessment): bool
    {
        return $assessment->isOwnedBy($user);
    }

    public function grade(User $user, Assessment $assessment): bool
    {
        return $assessment->isOwnedBy($user);
    }
}
