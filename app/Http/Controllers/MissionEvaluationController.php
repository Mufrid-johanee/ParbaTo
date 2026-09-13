<?php

namespace App\Http\Controllers;

use App\Models\MissionEnrollment;
use App\Services\MissionProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionEvaluationController extends Controller
{
    public function __construct(
        protected MissionProgressService $progress
    ) {}

    public function index(Request $request): View
    {
        $submissions = MissionEnrollment::query()
            ->with(['mission', 'user', 'submission'])
            ->whereIn('status', ['submitted', 'completed'])
            ->whereHas('submission', fn ($q) => $q->whereIn('status', ['submitted', 'accepted']))
            ->latest('submitted_at')
            ->paginate(20);

        return view('learnquest.evaluations.index', compact('submissions'));
    }

    public function show(Request $request, MissionEnrollment $enrollment): View
    {
        $this->authorize('evaluate', $enrollment);

        $enrollment->load(['mission.tasks', 'mission.skills', 'user', 'submission', 'taskProgress']);

        return view('learnquest.evaluations.show', compact('enrollment'));
    }

    public function store(Request $request, MissionEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('evaluate', $enrollment);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $this->progress->evaluate(
            $enrollment,
            $request->user(),
            (float) $validated['score'],
            $validated['feedback']
        );

        return redirect()
            ->route('learnquest.evaluations.index')
            ->with('status', 'Evaluation saved. Mission marked complete and learning evidence stored.');
    }
}
