<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionTask;
use App\Services\MissionProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnQuestApiController extends Controller
{
    public function __construct(
        protected MissionProgressService $progress
    ) {}

    public function show(Request $request, Mission $mission): JsonResponse
    {
        abort_unless($mission->isPublished() || $request->user()->hasRole('teacher', 'admin'), 404);

        $mission->load(['tasks', 'resources', 'skills']);

        $enrollment = MissionEnrollment::query()
            ->with(['taskProgress', 'submission'])
            ->where('mission_id', $mission->id)
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'data' => [
                'mission' => $mission,
                'enrollment' => $enrollment,
            ],
        ]);
    }

    public function start(Request $request, Mission $mission): JsonResponse
    {
        $enrollment = $this->progress->start($mission, $request->user());

        return response()->json([
            'message' => 'Mission enrollment ready.',
            'data' => $enrollment->load(['taskProgress', 'submission']),
        ], $enrollment->wasRecentlyCreated ? 201 : 200);
    }

    public function enrollment(Request $request, Mission $mission): JsonResponse
    {
        $enrollment = MissionEnrollment::query()
            ->with(['taskProgress', 'submission', 'mission.tasks'])
            ->where('mission_id', $mission->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->authorize('view', $enrollment);

        return response()->json(['data' => $enrollment]);
    }

    public function completeTask(Request $request, Mission $mission, MissionTask $task): JsonResponse
    {
        abort_unless($task->mission_id === $mission->id, 404);

        $enrollment = MissionEnrollment::query()
            ->where('mission_id', $mission->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->authorize('completeTask', $enrollment);
        $enrollment = $this->progress->completeTask($enrollment, $task, $request->user());

        return response()->json([
            'message' => 'Task completed.',
            'data' => $enrollment,
        ]);
    }

    public function submit(Request $request, Mission $mission): JsonResponse
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

        $submission = $this->progress->submit($enrollment, $request->user(), $validated);

        return response()->json([
            'message' => 'Submission received.',
            'data' => [
                'submission' => $submission,
                'enrollment' => $enrollment->fresh(['taskProgress', 'submission']),
            ],
        ]);
    }

    public function evaluate(Request $request, MissionEnrollment $enrollment): JsonResponse
    {
        $this->authorize('evaluate', $enrollment);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $enrollment = $this->progress->evaluate(
            $enrollment,
            $request->user(),
            (float) $validated['score'],
            $validated['feedback']
        );

        return response()->json([
            'message' => 'Evaluation saved.',
            'data' => $enrollment,
        ]);
    }
}
