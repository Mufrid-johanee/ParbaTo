<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Classroom $classroom): bool
    {
        if ($classroom->isOwnedBy($user)) {
            return true;
        }

        return $classroom->hasMember($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN);
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $classroom->isOwnedBy($user);
    }

    public function manage(User $user, Classroom $classroom): bool
    {
        return $classroom->isOwnedBy($user);
    }

    public function removeMember(User $user, Classroom $classroom): bool
    {
        return $classroom->isOwnedBy($user);
    }

    public function join(User $user): bool
    {
        return $user->isStudent() || $user->hasRole(User::ROLE_ADMIN);
    }
}
