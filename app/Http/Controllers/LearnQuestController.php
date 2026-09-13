<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionTask;
use App\Services\MissionProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnQuestController extends Controller
{
    public function __construct(
        protected MissionProgressService $progress
    ) {}

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
            'active' => MissionEnrollment::query()->where('user_id', $request->user()->id)->whereIn('status', ['in_progress', 'discovered', 'submitted'])->count(),
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

        $completedTaskIds = $enrollment
            ? $enrollment->taskProgress->where('status', 'done')->pluck('mission_task_id')
            : collect();

        $tasksByPhase = $mission->tasks->groupBy('phase');

        return view('learnquest.workspace', compact(
            'mission',
            'enrollment',
            'completedTaskIds',
            'tasksByPhase'
        ));
    }

    public function start(Request $request, Mission $mission): RedirectResponse
    {
        $enrollment = $this->progress->start($mission, $request->user());

        if ($enrollment->wasRecentlyCreated) {
            return redirect()
                ->route('learnquest.show', $mission)
                ->with('status', 'Mission started. Begin with Discover tasks.');
        }

        return redirect()
            ->route('learnquest.show', $mission)
            ->with('status', 'Continuing your existing mission progress.');
    }

    public function completeTask(
        Request $request,
        Mission $mission,
        MissionTask $task
    ): RedirectResponse {
        abort_unless($task->mission_id === $mission->id, 404);

        $enrollment = MissionEnrollment::query()
            ->where('mission_id', $mission->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->authorize('completeTask', $enrollment);
        $this->progress->completeTask($enrollment, $task, $request->user());

        return back()->with('status', 'Task marked complete. Progress updated.');
    }

    public function submit(Request $request, Mission $mission): RedirectResponse
    {
        $enrollment = MissionEnrollment::query()
            ->where('mission_id', $mission->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->authorize('submit', $enrollment);

        $validated = $request->validate([
            'summary' => ['required', 'string', 'min:20', 'max:5000'],
            'repo_url' => ['nullable', 'url', 'max:500'],
            'demo_url' => ['nullable', 'url', 'max:500'],
        ]);

        $this->progress->submit($enrollment, $request->user(), $validated);

        return back()->with('status', 'Project submitted. Waiting for teacher evaluation.');
    }
}
