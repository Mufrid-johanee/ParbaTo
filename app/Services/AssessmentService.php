<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Classroom;
use App\Models\PortfolioItem;
use App\Models\Question;
use App\Models\User;
use App\Notifications\AssessmentGradedNotification;
use App\Notifications\AssessmentNeedsReviewNotification;
use App\Notifications\AssessmentPublishedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    public function __construct(
        protected AssessmentScoringService $scoring,
        protected MasteryService $mastery,
        protected FlexLearnRecommendationService $flexLearn,
    ) {}

    public function create(User $teacher, array $data): Assessment
    {
        return Assessment::query()->create([
            'course_id' => $data['course_id'] ?? null,
            'classroom_id' => $data['classroom_id'] ?? null,
            'mission_id' => $data['mission_id'] ?? null,
            'created_by' => $teacher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'quiz',
            'instructions' => $data['instructions'] ?? null,
            'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
            'pass_score' => $data['pass_score'] ?? 60,
            'max_attempts' => $data['max_attempts'] ?? 1,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'status' => 'draft',
        ]);
    }

    public function update(Assessment $assessment, array $data): Assessment
    {
        if ($assessment->status === 'archived') {
            throw ValidationException::withMessages(['status' => 'Archived assessments cannot be edited.']);
        }

        $assessment->fill(collect($data)->only([
            'course_id', 'classroom_id', 'mission_id', 'title', 'description', 'type',
            'instructions', 'time_limit_minutes', 'pass_score', 'max_attempts',
            'starts_at', 'ends_at',
        ])->all())->save();

        return $assessment->fresh();
    }

    public function addQuestion(Assessment $assessment, array $data): Question
    {
        if ($assessment->status === 'archived') {
            throw ValidationException::withMessages(['status' => 'Cannot add questions to archived assessments.']);
        }

        $position = $data['position'] ?? ((int) $assessment->questions()->max('position') + 1);

        return Question::query()->create([
            'assessment_id' => $assessment->id,
            'skill_id' => $data['skill_id'] ?? null,
            'type' => $data['type'],
            'prompt' => $data['prompt'],
            'options' => $data['options'] ?? null,
            'correct_answer' => $data['correct_answer'] ?? null,
            'explanation' => $data['explanation'] ?? null,
            'points' => $data['points'] ?? 1,
            'position' => $position,
        ]);
    }

    public function updateQuestion(Question $question, array $data): Question
    {
        $question->fill(collect($data)->only([
            'skill_id', 'type', 'prompt', 'options', 'correct_answer',
            'explanation', 'points', 'position',
        ])->all())->save();

        return $question->fresh();
    }

    public function publish(Assessment $assessment): Assessment
    {
        if ($assessment->questions()->count() < 1) {
            throw ValidationException::withMessages(['questions' => 'Add at least one question before publishing.']);
        }

        $assessment->forceFill(['status' => 'published'])->save();

        $this->notifyEligibleStudents($assessment, new AssessmentPublishedNotification($assessment));

        return $assessment->fresh();
    }

    public function unpublish(Assessment $assessment): Assessment
    {
        if ($assessment->attempts()->whereIn('status', ['submitted', 'graded'])->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Cannot unpublish after students have submitted attempts.',
            ]);
        }

        $assessment->forceFill(['status' => 'draft'])->save();

        return $assessment->fresh();
    }

    public function archive(Assessment $assessment): Assessment
    {
        $assessment->forceFill(['status' => 'archived'])->save();

        return $assessment->fresh();
    }

    public function studentCanAccess(User $student, Assessment $assessment): bool
    {
        if (! $assessment->isPublished() && ! $assessment->isOwnedBy($student)) {
            return false;
        }

        if ($student->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        if ($assessment->classroom_id) {
            return Classroom::query()
                ->whereKey($assessment->classroom_id)
                ->whereHas('members', fn ($q) => $q->where('user_id', $student->id)->where('status', 'active'))
                ->exists();
        }

        if ($assessment->course_id) {
            return $student->enrollments()->where('course_id', $assessment->course_id)->exists();
        }

        // Global published practice visible to students if no classroom/course scope
        return $student->isStudent();
    }

    public function startAttempt(User $student, Assessment $assessment): AssessmentAttempt
    {
        if (! $this->studentCanAccess($student, $assessment) || ! $assessment->isAvailableNow()) {
            throw ValidationException::withMessages(['assessment' => 'This assessment is not available.']);
        }

        $existing = AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
            if ($existing->isTimedOut()) {
                return $this->submitAttempt($existing);
            }

            return $existing;
        }

        $finished = AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $student->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($finished >= (int) $assessment->max_attempts) {
            throw ValidationException::withMessages(['attempts' => 'Maximum attempts reached.']);
        }

        return AssessmentAttempt::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'in_progress',
            'max_score' => $assessment->questions()->sum('points'),
            'started_at' => now(),
            'answers' => [],
        ]);
    }

    public function autosaveAnswers(AssessmentAttempt $attempt, array $answers): AssessmentAttempt
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages(['attempt' => 'This attempt can no longer be edited.']);
        }

        if ($attempt->isTimedOut()) {
            return $this->submitAttempt($attempt);
        }

        $attempt->loadMissing('assessment.questions');
        $questionIds = $attempt->assessment->questions->pluck('id')->all();

        return DB::transaction(function () use ($attempt, $answers, $questionIds) {
            $snapshot = $attempt->answers ?? [];

            foreach ($answers as $payload) {
                $questionId = (int) ($payload['question_id'] ?? 0);
                if (! in_array($questionId, $questionIds, true)) {
                    continue;
                }

                AttemptAnswer::query()->updateOrCreate(
                    [
                        'assessment_attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'selected_option' => $payload['selected_option'] ?? null,
                        'answer_text' => $payload['answer_text'] ?? null,
                        // Never trust client scoring fields
                        'is_correct' => null,
                        'points_awarded' => null,
                    ]
                );

                $snapshot[(string) $questionId] = [
                    'selected_option' => $payload['selected_option'] ?? null,
                    'answer_text' => $payload['answer_text'] ?? null,
                ];
            }

            $attempt->forceFill(['answers' => $snapshot])->save();

            return $attempt->fresh(['answerRecords']);
        });
    }

    public function submitAttempt(AssessmentAttempt $attempt): AssessmentAttempt
    {
        if (in_array($attempt->status, ['submitted', 'graded'], true)) {
            throw ValidationException::withMessages(['attempt' => 'Attempt already submitted.']);
        }

        return DB::transaction(function () use ($attempt) {
            $totals = $this->scoring->scoreAttempt($attempt->fresh(['assessment.questions', 'answerRecords']));

            $status = $totals['needs_review'] ? 'submitted' : 'graded';

            $attempt->forceFill([
                'score' => $totals['score'],
                'max_score' => $totals['max_score'],
                'accuracy' => $totals['accuracy'],
                'status' => $status,
                'submitted_at' => $attempt->submitted_at ?? now(),
                'graded_at' => $status === 'graded' ? now() : null,
            ])->save();

            if ($status === 'graded') {
                $this->finalizeGradedAttempt($attempt->fresh(['assessment.questions', 'user', 'answerRecords']));
            } else {
                $teacher = $attempt->assessment->creator;
                if ($teacher) {
                    $teacher->notify(new AssessmentNeedsReviewNotification($attempt->fresh(['assessment', 'user'])));
                }
            }

            return $attempt->fresh(['answerRecords', 'assessment']);
        });
    }

    public function gradeShortAnswers(AssessmentAttempt $attempt, User $teacher, array $grades): AssessmentAttempt
    {
        if (! $attempt->assessment->isOwnedBy($teacher)) {
            abort(403);
        }

        if (! in_array($attempt->status, ['submitted', 'graded'], true)) {
            throw ValidationException::withMessages(['attempt' => 'Attempt is not ready for grading.']);
        }

        return DB::transaction(function () use ($attempt, $teacher, $grades) {
            $attempt->loadMissing(['answerRecords.question', 'assessment.questions']);

            foreach ($grades as $grade) {
                $answer = $attempt->answerRecords->firstWhere('question_id', (int) ($grade['question_id'] ?? 0));
                if (! $answer || $answer->question?->type !== 'short_answer') {
                    continue;
                }

                $this->scoring->applyTeacherGrade(
                    $answer,
                    (float) ($grade['points_awarded'] ?? 0),
                    $grade['teacher_feedback'] ?? null
                );
            }

            $totals = $this->scoring->scoreAttempt($attempt->fresh(['assessment.questions', 'answerRecords']));

            if ($totals['needs_review']) {
                $attempt->forceFill([
                    'score' => $totals['score'],
                    'max_score' => $totals['max_score'],
                    'accuracy' => $totals['accuracy'],
                    'status' => 'submitted',
                ])->save();

                return $attempt->fresh();
            }

            $attempt->forceFill([
                'score' => $totals['score'],
                'max_score' => $totals['max_score'],
                'accuracy' => $totals['accuracy'],
                'status' => 'graded',
                'graded_at' => now(),
                'graded_by' => $teacher->id,
            ])->save();

            $this->finalizeGradedAttempt($attempt->fresh(['assessment.questions', 'user', 'answerRecords']));

            return $attempt->fresh(['answerRecords', 'assessment']);
        });
    }

    public function finalizeGradedAttempt(AssessmentAttempt $attempt): void
    {
        // Idempotent evidence per skill + attempt
        $this->mastery->applyAssessmentAttempt($attempt);

        $percentage = (float) ($attempt->accuracy ?? 0);

        PortfolioItem::query()->updateOrCreate(
            [
                'user_id' => $attempt->user_id,
                'source_type' => AssessmentAttempt::class,
                'source_id' => $attempt->id,
            ],
            [
                'title' => $attempt->assessment->title,
                'summary' => sprintf('Assessment result: %s%%', number_format($percentage, 1)),
                'evidence' => [
                    'assessment_id' => $attempt->assessment_id,
                    'attempt_id' => $attempt->id,
                    'score' => (float) $attempt->score,
                    'max_score' => (float) $attempt->max_score,
                    'accuracy' => $percentage,
                    'passed' => $percentage >= (float) $attempt->assessment->pass_score,
                    'type' => 'assessment',
                    'completed_at' => ($attempt->graded_at ?? now())->toIso8601String(),
                ],
                'is_public' => false,
            ]
        );

        $this->flexLearn->refreshFor($attempt->user->fresh());

        $already = $attempt->user->notifications()
            ->where('type', AssessmentGradedNotification::class)
            ->where('data->attempt_id', $attempt->id)
            ->exists();

        if (! $already) {
            $attempt->user->notify(new AssessmentGradedNotification($attempt));
        }
    }

    protected function notifyEligibleStudents(Assessment $assessment, $notification): void
    {
        $students = collect();

        if ($assessment->classroom_id) {
            $students = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereHas('classroomMemberships', function ($q) use ($assessment) {
                    $q->where('classroom_id', $assessment->classroom_id)->where('status', 'active');
                })
                ->get();
        } elseif ($assessment->course_id) {
            $students = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereHas('enrollments', fn ($q) => $q->where('course_id', $assessment->course_id))
                ->get();
        }

        foreach ($students as $student) {
            $already = $student->notifications()
                ->where('type', AssessmentPublishedNotification::class)
                ->where('data->assessment_id', $assessment->id)
                ->exists();

            if (! $already) {
                $student->notify($notification);
            }
        }
    }
}
