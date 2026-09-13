<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionSubmission;
use App\Models\MissionTask;
use App\Models\MissionTaskProgress;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Notifications\MissionEvaluationCompletedNotification;
use App\Notifications\MissionSubmittedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MissionProgressService
{
    public const PHASES = [
        'discover',
        'learn',
        'practice',
        'build',
        'submit',
        'present',
        'evaluate',
    ];

    public function start(Mission $mission, User $student): MissionEnrollment
    {
        abort_unless($mission->isPublished(), 404);

        $enrollment = MissionEnrollment::query()->firstOrCreate(
            [
                'mission_id' => $mission->id,
                'user_id' => $student->id,
            ],
            [
                'status' => 'in_progress',
                'lifecycle_phase' => 'discover',
                'progress_percent' => 0,
                'started_at' => now(),
            ]
        );

        $created = $enrollment->wasRecentlyCreated;

        if ($enrollment->status === 'discovered') {
            $enrollment->forceFill([
                'status' => 'in_progress',
                'started_at' => $enrollment->started_at ?? now(),
            ])->save();
        }

        $enrollment->load(['taskProgress', 'submission']);
        $enrollment->wasRecentlyCreated = $created;

        return $enrollment;
    }

    public function completeTask(MissionEnrollment $enrollment, MissionTask $task, User $actor): MissionEnrollment
    {
        if ($enrollment->user_id !== $actor->id) {
            abort(403, 'You cannot complete tasks for another student.');
        }

        if ($enrollment->mission_id !== $task->mission_id) {
            throw ValidationException::withMessages([
                'task' => 'This task does not belong to the enrolled mission.',
            ]);
        }

        if (in_array($enrollment->status, ['completed', 'evaluated'], true)) {
            throw ValidationException::withMessages([
                'task' => 'This mission is already finished.',
            ]);
        }

        if ($task->phase === 'evaluate') {
            throw ValidationException::withMessages([
                'task' => 'Evaluation tasks are completed by a teacher after review.',
            ]);
        }

        return DB::transaction(function () use ($enrollment, $task) {
            MissionTaskProgress::query()->updateOrCreate(
                [
                    'mission_enrollment_id' => $enrollment->id,
                    'mission_task_id' => $task->id,
                ],
                [
                    'status' => 'done',
                    'completed_at' => now(),
                ]
            );

            return $this->recalculate($enrollment->fresh(['mission.tasks', 'taskProgress', 'submission']));
        });
    }

    public function submit(
        MissionEnrollment $enrollment,
        User $actor,
        array $data
    ): MissionSubmission {
        if ($enrollment->user_id !== $actor->id) {
            abort(403, 'You cannot submit for another student.');
        }

        if (in_array($enrollment->status, ['completed', 'evaluated'], true)) {
            throw ValidationException::withMessages([
                'submission' => 'This mission is already evaluated.',
            ]);
        }

        $mission = $enrollment->mission()->with('tasks')->firstOrFail();
        $studentTasks = $mission->tasks
            ->where('is_required', true)
            ->where('phase', '!=', 'evaluate');

        $doneIds = $enrollment->taskProgress()
            ->where('status', 'done')
            ->pluck('mission_task_id');

        $incomplete = $studentTasks->reject(fn (MissionTask $task) => $doneIds->contains($task->id));

        if ($incomplete->isNotEmpty()) {
            throw ValidationException::withMessages([
                'submission' => 'Complete all required Discover→Present tasks before submitting.',
            ]);
        }

        return DB::transaction(function () use ($enrollment, $actor, $data) {
            $submission = MissionSubmission::query()->updateOrCreate(
                [
                    'mission_enrollment_id' => $enrollment->id,
                    'user_id' => $actor->id,
                ],
                [
                    'summary' => $data['summary'],
                    'repo_url' => $data['repo_url'] ?? null,
                    'demo_url' => $data['demo_url'] ?? null,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'score' => null,
                    'feedback' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]
            );

            $enrollment->forceFill([
                'status' => 'submitted',
                'submitted_at' => now(),
                'lifecycle_phase' => 'evaluate',
            ])->save();

            $this->recalculate($enrollment->fresh(['mission.tasks', 'taskProgress', 'submission']));

            $teacher = $enrollment->mission->creator;
            if ($teacher) {
                $already = $teacher->notifications()
                    ->where('type', MissionSubmittedNotification::class)
                    ->where('data->enrollment_id', $enrollment->id)
                    ->exists();
                if (! $already) {
                    $teacher->notify(new MissionSubmittedNotification($enrollment->fresh(['mission', 'user'])));
                }
            }

            return $submission->fresh();
        });
    }

    public function evaluate(
        MissionEnrollment $enrollment,
        User $teacher,
        float $score,
        string $feedback
    ): MissionEnrollment {
        if (! $teacher->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN)) {
            abort(403, 'Only teachers can evaluate missions.');
        }

        $submission = $enrollment->submission;
        if (! $submission || $submission->status !== 'submitted') {
            throw ValidationException::withMessages([
                'evaluation' => 'There is no submitted project waiting for evaluation.',
            ]);
        }

        return DB::transaction(function () use ($enrollment, $teacher, $score, $feedback, $submission) {
            $submission->forceFill([
                'status' => 'accepted',
                'score' => $score,
                'feedback' => $feedback,
                'reviewed_by' => $teacher->id,
                'reviewed_at' => now(),
            ])->save();

            $evaluateTasks = $enrollment->mission->tasks()
                ->where('phase', 'evaluate')
                ->get();

            foreach ($evaluateTasks as $task) {
                MissionTaskProgress::query()->updateOrCreate(
                    [
                        'mission_enrollment_id' => $enrollment->id,
                        'mission_task_id' => $task->id,
                    ],
                    [
                        'status' => 'done',
                        'completed_at' => now(),
                    ]
                );
            }

            $enrollment = $this->recalculate($enrollment->fresh(['mission.tasks', 'mission.skills', 'taskProgress', 'submission', 'user']));

            $enrollment->forceFill([
                'status' => 'completed',
                'lifecycle_phase' => 'evaluate',
                'progress_percent' => 100,
                'completed_at' => now(),
            ])->save();

            $this->storeLearningEvidence($enrollment, $score);

            $student = $enrollment->user;
            if ($student) {
                $already = $student->notifications()
                    ->where('type', MissionEvaluationCompletedNotification::class)
                    ->where('data->enrollment_id', $enrollment->id)
                    ->exists();
                if (! $already) {
                    $student->notify(new MissionEvaluationCompletedNotification($enrollment->fresh(['mission']), $score));
                }
            }

            return $enrollment->fresh(['mission', 'submission', 'taskProgress']);
        });
    }

    public function recalculate(MissionEnrollment $enrollment): MissionEnrollment
    {
        $mission = $enrollment->mission()->with('tasks')->firstOrFail();
        $requiredTasks = $mission->tasks->where('is_required', true)->values();
        $total = $requiredTasks->count();

        $doneIds = $enrollment->taskProgress()
            ->where('status', 'done')
            ->pluck('mission_task_id');

        $completed = $requiredTasks->filter(fn (MissionTask $task) => $doneIds->contains($task->id))->count();
        $percent = $total === 0 ? 0 : (int) round(($completed / $total) * 100);

        $phase = $this->determinePhase($requiredTasks, $doneIds);

        $status = $enrollment->status;
        if ($status !== 'completed') {
            if ($enrollment->submission?->status === 'accepted') {
                $status = 'completed';
            } elseif ($enrollment->submission?->status === 'submitted') {
                $status = 'submitted';
            } elseif ($completed > 0 || $enrollment->started_at) {
                $status = $status === 'discovered' ? 'in_progress' : $status;
                if (! in_array($status, ['submitted', 'evaluated', 'completed'], true)) {
                    $status = 'in_progress';
                }
            }
        }

        $enrollment->forceFill([
            'progress_percent' => min(100, $percent),
            'lifecycle_phase' => $phase,
            'status' => $status,
        ])->save();

        return $enrollment->fresh(['mission.tasks', 'taskProgress', 'submission']);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, MissionTask>  $requiredTasks
     * @param  \Illuminate\Support\Collection<int, int>  $doneIds
     */
    public function determinePhase($requiredTasks, $doneIds): string
    {
        foreach (self::PHASES as $phase) {
            $phaseTasks = $requiredTasks->where('phase', $phase);
            if ($phaseTasks->isEmpty()) {
                continue;
            }

            $allDone = $phaseTasks->every(fn (MissionTask $task) => $doneIds->contains($task->id));
            if (! $allDone) {
                return $phase;
            }
        }

        return 'evaluate';
    }

    protected function storeLearningEvidence(MissionEnrollment $enrollment, float $score): void
    {
        $user = $enrollment->user;
        $mission = $enrollment->mission;

        $xpGain = (int) $mission->xp_reward;
        $user->forceFill([
            'xp' => $user->xp + $xpGain,
            'level' => max(1, (int) floor(($user->xp + $xpGain) / 500) + 1),
        ])->save();

        $mastery = app(MasteryService::class);
        $mastery->applyMissionEvaluation($enrollment, $score);

        PortfolioItem::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'source_type' => MissionEnrollment::class,
                'source_id' => $enrollment->id,
            ],
            [
                'title' => $mission->title,
                'summary' => $enrollment->submission?->summary,
                'evidence' => [
                    'mission_id' => $mission->id,
                    'score' => $score,
                    'feedback' => $enrollment->submission?->feedback,
                    'xp_awarded' => $xpGain,
                    'skills' => $mission->skills->pluck('name')->values()->all(),
                    'completed_at' => now()->toIso8601String(),
                ],
                'is_public' => false,
            ]
        );

        $flex = app(FlexLearnRecommendationService::class);
        $flex->markCompletedForMission($user, $mission->id);
        $flex->refreshFor($user->fresh());
    }
}
