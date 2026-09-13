<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\Recommendation;
use App\Services\BadgeService;
use App\Services\FlexLearnRecommendationService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        FlexLearnRecommendationService $flexLearn,
        XpService $xp,
        BadgeService $badges
    ): View {
        $user = $request->user();

        $flexLearn->generateFor($user);

        $activeMissions = MissionEnrollment::query()
            ->with('mission')
            ->where('user_id', $user->id)
            ->whereIn('status', ['discovered', 'in_progress', 'submitted'])
            ->latest()
            ->take(5)
            ->get();

        $recommendations = Recommendation::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->take(5)
            ->get();

        $liveSession = ClassSession::query()
            ->with(['classroom.course', 'classroom.teacher'])
            ->where('status', 'live')
            ->where(function ($q) use ($user) {
                $q->whereHas('classroom.members', fn ($m) => $m->where('user_id', $user->id)->where('status', 'active'))
                    ->orWhereHas('classroom', fn ($c) => $c->where('teacher_id', $user->id));
            })
            ->latest('started_at')
            ->first();

        $classrooms = $user->hasRole('teacher', 'admin')
            ? \App\Models\Classroom::query()
                ->where('teacher_id', $user->id)
                ->where('status', 'active')
                ->withCount(['members' => fn ($q) => $q->where('status', 'active')])
                ->latest()
                ->take(5)
                ->get()
            : \App\Models\Classroom::query()
                ->where('status', 'active')
                ->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
                ->withCount(['members' => fn ($q) => $q->where('status', 'active')])
                ->latest()
                ->take(5)
                ->get();

        $missionCount = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $availableMissions = Mission::query()
            ->where('status', 'published')
            ->latest()
            ->take(3)
            ->get();

        $level = $user->isStudent() ? $xp->getLevel((int) $user->xp) : null;
        $recentXp = $user->isStudent() ? $xp->getRecentXp($user, 5) : collect();
        $badgeCount = $user->isStudent() ? $badges->catalogFor($user)['count'] : 0;

        return view('dashboard.index', compact(
            'user',
            'activeMissions',
            'recommendations',
            'liveSession',
            'missionCount',
            'availableMissions',
            'classrooms',
            'level',
            'recentXp',
            'badgeCount'
        ));
    }
}
