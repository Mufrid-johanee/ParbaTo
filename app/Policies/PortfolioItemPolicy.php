<?php

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\Classroom;

class PortfolioItemPolicy
{
    public function viewOwn(User $user): bool
    {
        return $user->isStudent() || $user->hasRole(User::ROLE_ADMIN);
    }

    public function viewStudent(User $viewer, User $student): bool
    {
        if ((int) $viewer->id === (int) $student->id) {
            return true;
        }

        if ($viewer->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        if (! $viewer->isTeacher()) {
            return false;
        }

        return Classroom::query()
            ->where('teacher_id', $viewer->id)
            ->whereHas('members', fn ($q) => $q->where('user_id', $student->id)->where('status', 'active'))
            ->exists();
    }

    public function view(User $user, PortfolioItem $item): bool
    {
        return $this->viewStudent($user, $item->user);
    }
}
