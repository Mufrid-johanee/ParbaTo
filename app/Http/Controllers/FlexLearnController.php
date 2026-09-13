<?php

namespace App\Http\Controllers;

use App\Models\MissionEnrollment;
use App\Models\Recommendation;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\FlexLearnRecommendationService;
use App\Services\MasteryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlexLearnController extends Controller
{
    public function index(
        Request $request,
        FlexLearnRecommendationService $flexLearn,
        MasteryService $mastery
    ): View {
        $user = $request->user();

        try {
            $flexLearn->refreshFor($user);
            $error = null;
        } catch (\Throwable $e) {
            report($e);
            $error = 'We couldn\'t update your learning path right now. Showing the latest saved data.';
        }

        $skills = StudentSkill::query()
            ->with('skill')
            ->where('user_id', $user->id)
            ->orderByDesc('mastery')
            ->get();

        $bands = $mastery->classify($user);

        $recommendations = Recommendation::query()
            ->with(['skill', 'mission'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'started'])
            ->orderBy('sort_order')
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->get();

        $pathNodes = $this->buildLearningPath($user, $recommendations);

        return view('flexlearn.index', compact(
            'user',
            'skills',
            'bands',
            'recommendations',
            'pathNodes',
            'error'
        ));
    }

    public function startRecommendation(
        Request $request,
        Recommendation $recommendation,
        FlexLearnRecommendationService $flexLearn
    ): RedirectResponse {
        abort_unless($recommendation->user_id === $request->user()->id, 403);

        $flexLearn->start($recommendation, $request->user());

        $url = $recommendation->action_url ?: route('learnquest.index');

        return redirect()->to($url)->with('status', 'Recommendation started. Good luck on your next step.');
    }

    public function dismissRecommendation(
        Request $request,
        Recommendation $recommendation,
        FlexLearnRecommendationService $flexLearn
    ): RedirectResponse {
        abort_unless($recommendation->user_id === $request->user()->id, 403);

        $flexLearn->dismiss($recommendation, $request->user());

        return back()->with('status', 'Recommendation dismissed.');
    }

    public function teacherIndex(Request $request): View
    {
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with(['studentSkills.skill'])
            ->orderBy('name')
            ->get()
            ->map(function (User $student) {
                $skills = $student->studentSkills;
                $needs = $skills->filter(fn (StudentSkill $s) => $s->mastery < MasteryService::BAND_NEEDS_SUPPORT)->values();
                $strengths = $skills->filter(fn (StudentSkill $s) => $s->mastery >= MasteryService::BAND_PROFICIENT)->values();

                return [
                    'user' => $student,
                    'avg_mastery' => $skills->count() ? (int) round($skills->avg('mastery')) : null,
                    'needs' => $needs,
                    'strengths' => $strengths,
                    'active_recs' => Recommendation::query()
                        ->where('user_id', $student->id)
                        ->whereIn('status', ['active', 'started'])
                        ->count(),
                ];
            });

        return view('flexlearn.teacher', compact('students'));
    }

    public function teacherShow(Request $request, User $student, FlexLearnRecommendationService $flexLearn, MasteryService $mastery): View
    {
        abort_unless($student->isStudent(), 404);

        $flexLearn->refreshFor($student);
        $skills = StudentSkill::query()->with('skill')->where('user_id', $student->id)->orderBy('mastery')->get();
        $bands = $mastery->classify($student);
        $recommendations = Recommendation::query()
            ->with(['skill', 'mission'])
            ->where('user_id', $student->id)
            ->whereIn('status', ['active', 'started'])
            ->orderBy('sort_order')
            ->get();

        return view('flexlearn.teacher-show', compact('student', 'skills', 'bands', 'recommendations'));
    }

    /**
     * @return list<array{label: string, state: string, detail: string|null}>
     */
    protected function buildLearningPath(User $user, $recommendations): array
    {
        $completed = MissionEnrollment::query()
            ->with('mission')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->take(4)
            ->get();

        $nodes = [];

        foreach ($completed as $enrollment) {
            $nodes[] = [
                'label' => $enrollment->mission->title ?? 'Completed mission',
                'state' => 'done',
                'detail' => 'Completed · evidence recorded',
            ];
        }

        $current = $recommendations->first();
        if ($current) {
            $nodes[] = [
                'label' => $current->title,
                'state' => 'current',
                'detail' => $current->skill?->name
                    ? $current->skill->name.' · '.$current->reason
                    : $current->reason,
            ];
        }

        $upcoming = $recommendations->skip(1)->take(2);
        foreach ($upcoming as $rec) {
            $nodes[] = [
                'label' => $rec->title,
                'state' => 'planned',
                'detail' => $rec->skill?->name,
            ];
        }

        if ($nodes === []) {
            $nodes[] = [
                'label' => 'Unlock your path',
                'state' => 'empty',
                'detail' => 'Complete your first mission to unlock your personalized learning path.',
            ];
        }

        return $nodes;
    }
}
