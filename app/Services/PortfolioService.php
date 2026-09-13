<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\LearningEvidence;
use App\Models\MissionEnrollment;
use App\Models\PortfolioItem;
use App\Models\Recommendation;
use App\Models\StudentSkill;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PortfolioService
{
    public function __construct(protected MasteryService $mastery) {}

    public function headerStats(User $student): array
    {
        $missionsCompleted = MissionEnrollment::query()
            ->where('user_id', $student->id)
            ->where('status', 'completed')
            ->count();

        $assessmentsPassed = AssessmentAttempt::query()
            ->with('assessment:id,pass_score')
            ->where('user_id', $student->id)
            ->where('status', 'graded')
            ->get()
            ->filter(fn ($a) => (float) $a->accuracy >= (float) ($a->assessment->pass_score ?? 60))
            ->count();

        $sessionsAttended = AttendanceRecord::query()
            ->where('user_id', $student->id)
            ->whereIn('status', ['present', 'late'])
            ->select('class_session_id')
            ->distinct()
            ->count();

        return [
            'missions_completed' => $missionsCompleted,
            'assessments_passed' => $assessmentsPassed,
            'sessions_attended' => $sessionsAttended,
            'xp' => (int) $student->xp,
            'level' => (int) $student->level,
            'member_since' => $student->created_at,
        ];
    }

    public function masteryProfile(User $student): array
    {
        $skills = StudentSkill::query()
            ->with('skill')
            ->where('user_id', $student->id)
            ->orderByDesc('mastery')
            ->get();

        $classified = $this->mastery->classify($student);

        return [
            'skills' => $skills,
            'top' => $skills->take(3),
            'needs_support' => $classified['needs_support'],
            'bands' => $skills->groupBy(fn (StudentSkill $s) => MasteryService::bandKey((int) $s->mastery)),
        ];
    }

    public function timeline(User $student, int $perPage = 15): LengthAwarePaginator
    {
        return PortfolioItem::query()
            ->where('user_id', $student->id)
            ->latest()
            ->paginate($perPage);
    }

    public function evidence(User $student, int $perPage = 15): LengthAwarePaginator
    {
        return LearningEvidence::query()
            ->with('skill')
            ->where('user_id', $student->id)
            ->latest()
            ->paginate($perPage);
    }

    public function recommendations(User $student): Collection
    {
        return Recommendation::query()
            ->with(['skill', 'mission'])
            ->where('user_id', $student->id)
            ->whereIn('status', ['active', 'started'])
            ->orderBy('sort_order')
            ->get();
    }
}
