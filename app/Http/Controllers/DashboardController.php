<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\Recommendation;
use App\Services\FlexLearnRecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FlexLearnRecommendationService $flexLearn): View
    {
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
            ->whereHas('classroom.members', fn ($q) => $q->where('user_id', $user->id))
            ->latest('started_at')
            ->first();

        $missionCount = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $availableMissions = Mission::query()
            ->where('status', 'published')
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard.index', compact(
            'user',
            'activeMissions',
            'recommendations',
            'liveSession',
            'missionCount',
            'availableMissions'
        ));
    }
}
