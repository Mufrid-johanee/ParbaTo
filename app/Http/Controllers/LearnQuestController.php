<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LearnQuestController extends Controller
{
    public function index(Request $request): View
    {
        $query = Mission::query()
            ->with(['skills', 'creator'])
            ->where('status', 'published');

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->string('difficulty'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', $q)
                    ->orWhere('description', 'like', $q);
            });
        }

        $missions = $query->latest()->paginate(12)->withQueryString();

        $enrollments = MissionEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->get()
            ->keyBy('mission_id');

        $counts = [
            'all' => Mission::query()->where('status', 'published')->count(),
            'active' => MissionEnrollment::query()->where('user_id', $request->user()->id)->whereIn('status', ['in_progress', 'discovered'])->count(),
            'completed' => MissionEnrollment::query()->where('user_id', $request->user()->id)->where('status', 'completed')->count(),
        ];

        return view('learnquest.index', compact('missions', 'enrollments', 'counts'));
    }

    public function show(Request $request, Mission $mission): View
    {
        abort_unless($mission->isPublished() || $request->user()->hasRole('teacher', 'admin'), 404);

        $mission->load(['tasks', 'resources', 'skills']);

        $enrollment = MissionEnrollment::query()
            ->with(['taskProgress', 'submission'])
            ->where('mission_id', $mission->id)
            ->where('user_id', $request->user()->id)
            ->first();

        return view('learnquest.workspace', compact('mission', 'enrollment'));
    }

    public function start(Request $request, Mission $mission): RedirectResponse
    {
        abort_unless($mission->isPublished(), 404);

        $enrollment = MissionEnrollment::query()->firstOrCreate(
            [
                'mission_id' => $mission->id,
                'user_id' => $request->user()->id,
            ],
            [
                'status' => 'in_progress',
                'lifecycle_phase' => 'discover',
                'progress_percent' => 0,
                'started_at' => now(),
            ]
        );

        if ($enrollment->status === 'discovered') {
            $enrollment->update([
                'status' => 'in_progress',
                'started_at' => $enrollment->started_at ?? now(),
            ]);
        }

        return redirect()->route('learnquest.show', $mission);
    }
}
