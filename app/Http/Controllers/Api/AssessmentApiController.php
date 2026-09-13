<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Question;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentApiController extends Controller
{
    public function __construct(protected AssessmentService $assessments) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isTeacher() || $user->isAdmin()) {
            $items = Assessment::query()
                ->withCount('questions')
                ->where('created_by', $user->id)
                ->latest()
                ->paginate(20);
        } else {
            $classroomIds = $user->classroomMemberships()->where('status', 'active')->pluck('classroom_id');
            $courseIds = $user->enrollments()->pluck('course_id');
            $items = Assessment::query()
                ->withCount('questions')
                ->where('status', 'published')
                ->where(function ($q) use ($classroomIds, $courseIds) {
                    $q->whereIn('classroom_id', $classroomIds)
                        ->orWhereIn('course_id', $courseIds);
                })
                ->latest()
                ->paginate(20);
        }

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Assessment::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'instructions' => ['nullable', 'string'],
        ]);

        $assessment = $this->assessments->create($request->user(), $data);

        return response()->json($assessment, 201);
    }

    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('view', $assessment);
        $assessment->load(['questions' => function ($q) use ($request, $assessment) {
            if (! $assessment->isOwnedBy($request->user())) {
                $q->select(['id', 'assessment_id', 'skill_id', 'type', 'prompt', 'options', 'points', 'position']);
            }
        }]);

        return response()->json($assessment);
    }

    public function update(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('update', $assessment);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'instructions' => ['nullable', 'string'],
        ]);

        return response()->json($this->assessments->update($assessment, $data));
    }

    public function destroy(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('delete', $assessment);
        $this->assessments->archive($assessment);

        return response()->json(['ok' => true]);
    }

    public function storeQuestion(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('update', $assessment);
        $data = $request->validate([
            'type' => ['required', 'in:mcq,true_false,short_answer'],
            'prompt' => ['required', 'string'],
            'skill_id' => ['nullable', 'exists:skills,id'],
            'points' => ['required', 'integer', 'min:1'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'array'],
            'explanation' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($this->assessments->addQuestion($assessment, $data), 201);
    }

    public function updateQuestion(Request $request, Question $question): JsonResponse
    {
        $this->authorize('update', $question->assessment);
        $data = $request->validate([
            'type' => ['sometimes', 'in:mcq,true_false,short_answer'],
            'prompt' => ['sometimes', 'string'],
            'skill_id' => ['nullable', 'exists:skills,id'],
            'points' => ['sometimes', 'integer', 'min:1'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'array'],
            'explanation' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($this->assessments->updateQuestion($question, $data));
    }

    public function destroyQuestion(Request $request, Question $question): JsonResponse
    {
        $this->authorize('update', $question->assessment);
        $question->delete();

        return response()->json(['ok' => true]);
    }

    public function startAttempt(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('view', $assessment);
        $attempt = $this->assessments->startAttempt($request->user(), $assessment);

        return response()->json($attempt->load('answerRecords'), 201);
    }

    public function showAttempt(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->authorize('view', $attempt);
        $attempt->load(['answerRecords', 'assessment.questions']);

        if (! $attempt->assessment->isOwnedBy($request->user()) && $attempt->isInProgress()) {
            $attempt->assessment->questions->each(function ($q) {
                unset($q->correct_answer, $q->explanation);
            });
        }

        return response()->json($attempt);
    }

    public function saveAnswers(Request $request, AssessmentAttempt $attempt): JsonResponse
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
            'attempt' => $attempt,
            'remaining_seconds' => $attempt->remainingSeconds(),
        ]);
    }

    public function submit(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->authorize('submit', $attempt);
        $attempt = $this->assessments->submitAttempt($attempt);

        return response()->json($attempt);
    }

    public function result(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->authorize('view', $attempt);
        abort_unless(in_array($attempt->status, ['submitted', 'graded'], true), 404);

        return response()->json($attempt->load(['answerRecords', 'assessment']));
    }

    public function grade(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->authorize('grade', $attempt);
        $data = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.question_id' => ['required', 'integer'],
            'grades.*.points_awarded' => ['required', 'numeric', 'min:0'],
            'grades.*.teacher_feedback' => ['nullable', 'string'],
        ]);

        $attempt = $this->assessments->gradeShortAnswers($attempt, $request->user(), $data['grades']);

        return response()->json($attempt);
    }
}
