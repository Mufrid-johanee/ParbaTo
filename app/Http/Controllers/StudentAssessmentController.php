<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAssessmentController extends Controller
{
    public function __construct(protected AssessmentService $assessments) {}

    public function index(Request $request): View
    {
        $student = $request->user();

        $classroomIds = $student->classroomMemberships()->where('status', 'active')->pluck('classroom_id');
        $courseIds = $student->enrollments()->pluck('course_id');

        $items = Assessment::query()
            ->withCount('questions')
            ->with(['classroom', 'course'])
            ->where('status', 'published')
            ->where(function ($q) use ($classroomIds, $courseIds) {
                $q->whereIn('classroom_id', $classroomIds)
                    ->orWhereIn('course_id', $courseIds)
                    ->orWhere(function ($inner) {
                        $inner->whereNull('classroom_id')->whereNull('course_id');
                    });
            })
            ->latest()
            ->paginate(12);

        $attempts = AssessmentAttempt::query()
            ->where('user_id', $student->id)
            ->get()
            ->groupBy('assessment_id');

        return view('assessments.student.index', compact('items', 'attempts'));
    }

    public function show(Request $request, Assessment $assessment): View
    {
        $this->authorize('view', $assessment);

        $assessment->loadCount('questions');
        $myAttempts = AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('assessments.student.show', compact('assessment', 'myAttempts'));
    }

    public function start(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('view', $assessment);

        $attempt = $this->assessments->startAttempt($request->user(), $assessment);

        return redirect()->route('student.attempts.take', $attempt);
    }

    public function take(Request $request, AssessmentAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->status === 'graded' || $attempt->status === 'submitted') {
            return redirect()->route('student.attempts.result', $attempt);
        }

        if ($attempt->isTimedOut()) {
            $this->assessments->submitAttempt($attempt);

            return redirect()->route('student.attempts.result', $attempt->fresh());
        }

        $attempt->load(['assessment.questions.skill', 'answerRecords']);

        return view('assessments.student.take', [
            'attempt' => $attempt,
            'assessment' => $attempt->assessment,
            'remainingSeconds' => $attempt->remainingSeconds(),
        ]);
    }

    public function autosave(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->authorize('update', $attempt);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_option' => ['nullable', 'string'],
            'answers.*.answer_text' => ['nullable', 'string'],
        ]);

        $attempt = $this->assessments->autosaveAnswers($attempt, $data['answers']);

        return response()->json([
            'ok' => true,
            'status' => $attempt->status,
            'remaining_seconds' => $attempt->remainingSeconds(),
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    public function submit(Request $request, AssessmentAttempt $attempt): RedirectResponse
    {
        $this->authorize('submit', $attempt);

        if ($request->filled('answers')) {
            $data = $request->validate([
                'answers' => ['array'],
                'answers.*.question_id' => ['required', 'integer'],
                'answers.*.selected_option' => ['nullable', 'string'],
                'answers.*.answer_text' => ['nullable', 'string'],
            ]);
            $this->assessments->autosaveAnswers($attempt, $data['answers'] ?? []);
        }

        $this->assessments->submitAttempt($attempt->fresh());

        return redirect()
            ->route('student.attempts.result', $attempt->fresh())
            ->with('status', 'Assessment submitted.');
    }

    public function result(Request $request, AssessmentAttempt $attempt): View
    {
        $this->authorize('view', $attempt);
        abort_unless(in_array($attempt->status, ['submitted', 'graded'], true), 404);

        $attempt->load(['assessment.questions', 'answerRecords']);

        $showAnswers = $attempt->status === 'graded';

        return view('assessments.student.result', compact('attempt', 'showAnswers'));
    }
}
