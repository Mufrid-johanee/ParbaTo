<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Models\StudentSkill;
use App\Services\FlexLearnRecommendationService;
use App\Services\MasteryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlexLearnApiController extends Controller
{
    public function __construct(
        protected FlexLearnRecommendationService $flexLearn,
        protected MasteryService $mastery
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $this->flexLearn->refreshFor($user);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'We couldn\'t update your learning path right now. Please try again.',
            ], 503);
        }

        return response()->json([
            'data' => [
                'mastery' => $this->masteryPayload($user->id),
                'bands' => $this->bandsPayload($user),
                'recommendations' => $this->recommendationsPayload($user->id),
            ],
        ]);
    }

    public function mastery(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->masteryPayload($request->user()->id),
            'bands' => $this->bandsPayload($request->user()),
        ]);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $this->flexLearn->refreshFor($request->user());

        return response()->json([
            'data' => $this->recommendationsPayload($request->user()->id),
        ]);
    }

    public function start(Request $request, Recommendation $recommendation): JsonResponse
    {
        abort_unless($recommendation->user_id === $request->user()->id, 403);

        $rec = $this->flexLearn->start($recommendation, $request->user());

        return response()->json(['data' => $rec->load(['skill', 'mission'])]);
    }

    public function complete(Request $request, Recommendation $recommendation): JsonResponse
    {
        abort_unless($recommendation->user_id === $request->user()->id, 403);

        if ($recommendation->mission_id) {
            $this->flexLearn->markCompletedForMission($request->user(), $recommendation->mission_id);
        } else {
            $recommendation->forceFill(['status' => 'completed'])->save();
        }

        return response()->json(['data' => $recommendation->fresh()->load(['skill', 'mission'])]);
    }

    public function dismiss(Request $request, Recommendation $recommendation): JsonResponse
    {
        abort_unless($recommendation->user_id === $request->user()->id, 403);

        $rec = $this->flexLearn->dismiss($recommendation, $request->user());

        return response()->json(['data' => $rec]);
    }

    protected function masteryPayload(int $userId): array
    {
        return StudentSkill::query()
            ->with('skill:id,name,slug')
            ->where('user_id', $userId)
            ->orderByDesc('mastery')
            ->get()
            ->map(fn (StudentSkill $s) => [
                'skill_id' => $s->skill_id,
                'skill' => $s->skill?->name,
                'mastery' => (int) $s->mastery,
                'band' => $s->bandLabel(),
                'band_key' => $s->bandKey(),
                'evidence_count' => (int) $s->evidence_count,
                'last_assessed_at' => $s->last_assessed_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    protected function bandsPayload($user): array
    {
        $bands = $this->mastery->classify($user);

        return collect($bands)->map(fn ($items) => $items->map(fn (StudentSkill $s) => [
            'skill' => $s->skill?->name,
            'mastery' => (int) $s->mastery,
        ])->values())->all();
    }

    protected function recommendationsPayload(int $userId): array
    {
        return Recommendation::query()
            ->with(['skill:id,name', 'mission:id,title,slug,difficulty'])
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'started'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Recommendation $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'reason' => $r->reason,
                'priority' => $r->priority,
                'status' => $r->status,
                'skill' => $r->skill?->name,
                'mission' => $r->mission ? [
                    'id' => $r->mission->id,
                    'title' => $r->mission->title,
                    'slug' => $r->mission->slug,
                    'difficulty' => $r->mission->difficulty,
                ] : null,
                'action_label' => $r->action_label,
                'action_url' => $r->action_url,
            ])
            ->values()
            ->all();
    }
}
