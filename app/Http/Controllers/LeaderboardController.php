<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, XpService $xp, BadgeService $badges): View
    {
        $user = $request->user();
        abort_unless($user->isStudent() || $user->isAdmin(), 403);

        $classroomId = $request->integer('classroom') ?: null;
        $memberIds = null;

        if ($classroomId) {
            $classroom = Classroom::query()->findOrFail($classroomId);
            abort_unless($classroom->hasMember($user) || $user->isAdmin(), 403);
            $memberIds = ClassroomMember::query()
                ->where('classroom_id', $classroom->id)
                ->where('status', 'active')
                ->pluck('user_id');
        } elseif ($user->isStudent()) {
            $classroomIds = $user->classroomMemberships()->where('status', 'active')->pluck('classroom_id');
            $memberIds = ClassroomMember::query()
                ->whereIn('classroom_id', $classroomIds)
                ->where('status', 'active')
                ->pluck('user_id')
                ->unique();
        }

        $query = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->withCount('achievements')
            ->orderByDesc('xp')
            ->orderBy('name');

        if ($memberIds !== null) {
            $query->whereIn('id', $memberIds);
        }

        $rows = $query->paginate(20);

        $myRank = null;
        if ($user->isStudent()) {
            $higher = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->when($memberIds !== null, fn ($q) => $q->whereIn('id', $memberIds))
                ->where(function ($q) use ($user) {
                    $q->where('xp', '>', $user->xp)
                        ->orWhere(function ($inner) use ($user) {
                            $inner->where('xp', $user->xp)->where('name', '<', $user->name);
                        });
                })
                ->count();
            $myRank = $higher + 1;
        }

        $classrooms = $user->isStudent()
            ? Classroom::query()
                ->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
                ->orderBy('name')
                ->get()
            : collect();

        return view('gamification.leaderboard', [
            'rows' => $rows,
            'user' => $user,
            'myRank' => $myRank,
            'level' => $xp->getLevel((int) $user->xp),
            'classrooms' => $classrooms,
            'classroomId' => $classroomId,
            'badgeCount' => $badges->catalogFor($user)['count'] ?? 0,
        ]);
    }
}
