<?php

namespace App\Policies;

use App\Models\AssessmentAttempt;
use App\Models\User;

class AssessmentAttemptPolicy
{
    public function view(User $user, AssessmentAttempt $attempt): bool
    {
        if ((int) $attempt->user_id === (int) $user->id) {
            return true;
        }

        return $attempt->assessment && $attempt->assessment->isOwnedBy($user);
    }

    public function update(User $user, AssessmentAttempt $attempt): bool
    {
        return (int) $attempt->user_id === (int) $user->id && $attempt->isInProgress();
    }

    public function submit(User $user, AssessmentAttempt $attempt): bool
    {
        return (int) $attempt->user_id === (int) $user->id && $attempt->isInProgress();
    }

    public function grade(User $user, AssessmentAttempt $attempt): bool
    {
        return $attempt->assessment && $attempt->assessment->isOwnedBy($user);
    }
}
