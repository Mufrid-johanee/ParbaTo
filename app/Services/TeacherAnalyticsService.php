<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\LearningEvidence;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\StudentSkill;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TeacherAnalyticsService
{
    public function overview(User $teacher): array
    {
        return Cache::remember(
            'teacher.analytics.overview.'.$teacher->id,
            now()->addMinutes(5),
            function () use ($teacher) {
                return $this->computeOverview($teacher);
            }
        );
    }

    protected function computeOverview(User $teacher): array
    {
        $classroomIds = $this->ownedClassroomIds($teacher);

        $memberIds = ClassroomMember::query()
            ->whereIn('classroom_id', $classroomIds)
            ->where('status', 'active')
            ->pluck('user_id')
            ->unique();

        $sessionIds = ClassSession::query()->whereIn('classroom_id', $classroomIds)->pluck('id');

        $attendanceRows = AttendanceRecord::query()
            ->whereIn('class_session_id', $sessionIds)
            ->selectRaw("COUNT(*) as total, SUM(status IN ('present','late')) as present")
            ->first();

        $avgAttendance = ($attendanceRows && $attendanceRows->total > 0)
            ? round(($attendanceRows->present / $attendanceRows->total) * 100, 1)
            : 0;

        return [
            'total_students' => $memberIds->count(),
            'total_classrooms' => $classroomIds->count(),
            'total_missions' => Mission::query()->where('created_by', $teacher->id)->count(),
            'total_assessments' => Assessment::query()->where('created_by', $teacher->id)->count(),
            'total_sessions' => $sessionIds->count(),
            'average_attendance' => $avgAttendance,
            'learning_activity' => LearningEvidence::query()->whereIn('user_id', $memberIds)->count(),
        ];
    }

    public function classrooms(User $teacher): Collection
    {
        return Classroom::query()
            ->withCount(['members', 'sessions'])
            ->with(['sessions' => fn ($q) => $q->latest()->limit(1)])
            ->where('teacher_id', $teacher->id)
            ->orderBy('name')
            ->get()
            ->map(function (Classroom $classroom) {
                $sessionIds = ClassSession::query()->where('classroom_id', $classroom->id)->pluck('id');
                $stats = AttendanceRecord::query()
                    ->whereIn('class_session_id', $sessionIds)
                    ->selectRaw("COUNT(*) as total, SUM(status IN ('present','late')) as present")
                    ->first();

                $avg = ($stats && $stats->total > 0)
                    ? round(($stats->present / $stats->total) * 100, 1)
                    : 0;

                return [
                    'classroom' => $classroom,
                    'member_count' => $classroom->members_count,
                    'session_count' => $classroom->sessions_count,
                    'average_attendance' => $avg,
                    'latest_session' => $classroom->sessions->first(),
                    'status' => $classroom->status ?? 'active',
                ];
            });
    }

    public function classroomDetail(User $teacher, Classroom $classroom, float $lowThreshold = 75): array
    {
        abort_unless($classroom->isOwnedBy($teacher), 403);

        $members = $classroom->members()->with('user')->where('status', 'active')->get();
        $sessions = ClassSession::query()->where('classroom_id', $classroom->id)->orderBy('started_at')->get();

        $grid = [];
        $percentages = [];

        foreach ($members as $member) {
            $row = ['student' => $member->user, 'sessions' => []];
            $present = 0;
            foreach ($sessions as $session) {
                $record = AttendanceRecord::query()
                    ->where('class_session_id', $session->id)
                    ->where('user_id', $member->user_id)
                    ->first();
                $status = $record?->status ?? 'absent';
                if (in_array($status, ['present', 'late'], true)) {
                    $present++;
                }
                $row['sessions'][$session->id] = $status;
            }
            $pct = $sessions->count() > 0 ? round(($present / $sessions->count()) * 100, 1) : 0;
            $row['percentage'] = $pct;
            $row['low'] = $pct < $lowThreshold;
            $percentages[] = $pct;
            $grid[] = $row;
        }

        $trend = $sessions->map(function (ClassSession $session) use ($members) {
            $present = AttendanceRecord::query()
                ->where('class_session_id', $session->id)
                ->whereIn('user_id', $members->pluck('user_id'))
                ->whereIn('status', ['present', 'late'])
                ->count();
            $den = max(1, $members->count());

            return [
                'session_id' => $session->id,
                'label' => optional($session->started_at)->format('M j'),
                'percentage' => round(($present / $den) * 100, 1),
            ];
        });

        return compact('classroom', 'members', 'sessions', 'grid', 'trend', 'lowThreshold');
    }

    public function missionAnalytics(User $teacher): Collection
    {
        $memberIds = $this->memberIds($teacher);

        return Mission::query()
            ->where('created_by', $teacher->id)
            ->withCount('enrollments')
            ->get()
            ->map(function (Mission $mission) use ($memberIds) {
                $enrollments = MissionEnrollment::query()
                    ->where('mission_id', $mission->id)
                    ->whereIn('user_id', $memberIds)
                    ->with('submission')
                    ->get();

                $submitted = $enrollments->whereIn('status', ['submitted', 'completed'])->count();
                $evaluated = $enrollments->where('status', 'completed')->count();
                $scores = $enrollments->map(fn ($e) => $e->submission?->score)->filter();

                return [
                    'mission' => $mission,
                    'enrollments' => $enrollments->count(),
                    'submissions' => $submitted,
                    'evaluations' => $evaluated,
                    'not_submitted' => $enrollments->where('status', 'in_progress')->count()
                        + max(0, $memberIds->count() - $enrollments->count()),
                    'average_score' => $scores->count() ? round($scores->avg(), 1) : null,
                    'average_xp' => $mission->xp_reward,
                ];
            });
    }

    public function assessmentAnalytics(User $teacher): Collection
    {
        return Assessment::query()
            ->where('created_by', $teacher->id)
            ->withCount('questions')
            ->get()
            ->map(function (Assessment $assessment) {
                $attempts = AssessmentAttempt::query()->where('assessment_id', $assessment->id)->get();
                $graded = $attempts->where('status', 'graded');
                $passed = $graded->filter(fn ($a) => (float) $a->accuracy >= (float) $assessment->pass_score);

                $questionStats = $assessment->questions()->get()->map(function ($question) use ($attempts) {
                    $answers = DB::table('attempt_answers')
                        ->where('question_id', $question->id)
                        ->whereIn('assessment_attempt_id', $attempts->pluck('id'))
                        ->get();
                    $correct = $answers->where('is_correct', 1)->count();
                    $total = $answers->whereNotNull('is_correct')->count();

                    return [
                        'question_id' => $question->id,
                        'prompt' => $question->prompt,
                        'correct_rate' => $total > 0 ? round(($correct / $total) * 100, 1) : null,
                    ];
                });

                return [
                    'assessment' => $assessment,
                    'total_attempts' => $attempts->count(),
                    'submitted' => $attempts->whereIn('status', ['submitted', 'graded'])->count(),
                    'graded' => $graded->count(),
                    'pending_review' => $attempts->where('status', 'submitted')->count(),
                    'average_score' => $graded->count() ? round($graded->avg('accuracy'), 1) : null,
                    'pass_rate' => $graded->count() ? round(($passed->count() / $graded->count()) * 100, 1) : null,
                    'question_stats' => $questionStats,
                ];
            });
    }

    public function progressMatrix(User $teacher, ?Classroom $classroom = null): Collection
    {
        $classroomIds = $classroom
            ? collect([$classroom->id])->filter(fn ($id) => Classroom::query()->whereKey($id)->where('teacher_id', $teacher->id)->exists())
            : $this->ownedClassroomIds($teacher);

        $members = ClassroomMember::query()
            ->with('user')
            ->whereIn('classroom_id', $classroomIds)
            ->where('status', 'active')
            ->get()
            ->unique('user_id');

        return $members->map(function (ClassroomMember $member) use ($classroomIds) {
            $userId = $member->user_id;
            $sessionIds = ClassSession::query()->whereIn('classroom_id', $classroomIds)->pluck('id');
            $attTotal = AttendanceRecord::query()->whereIn('class_session_id', $sessionIds)->where('user_id', $userId)->count();
            $attPresent = AttendanceRecord::query()
                ->whereIn('class_session_id', $sessionIds)
                ->where('user_id', $userId)
                ->whereIn('status', ['present', 'late'])
                ->count();

            return [
                'student' => $member->user,
                'missions' => MissionEnrollment::query()->where('user_id', $userId)->where('status', 'completed')->count(),
                'assessments' => AssessmentAttempt::query()->where('user_id', $userId)->where('status', 'graded')->count(),
                'attendance' => $attTotal > 0 ? round(($attPresent / $attTotal) * 100, 1) : 0,
                'avg_mastery' => (int) round(StudentSkill::query()->where('user_id', $userId)->avg('mastery') ?? 0),
                'evidence' => LearningEvidence::query()->where('user_id', $userId)->count(),
            ];
        })->values();
    }

    public function atRisk(User $teacher, int $daysWithoutMission = 14, float $attendanceThreshold = 75, float $lowScore = 50): Collection
    {
        $matrix = $this->progressMatrix($teacher);

        return $matrix->map(function (array $row) use ($daysWithoutMission, $attendanceThreshold, $lowScore) {
            $reasons = [];
            $student = $row['student'];

            if ($row['attendance'] < $attendanceThreshold) {
                $reasons[] = sprintf('Attendance %s%%', $row['attendance']);
            }

            $lastMission = MissionEnrollment::query()
                ->where('user_id', $student->id)
                ->whereIn('status', ['submitted', 'completed'])
                ->latest('updated_at')
                ->value('updated_at');

            if (! $lastMission || $lastMission < now()->subDays($daysWithoutMission)) {
                $reasons[] = "No mission submission in {$daysWithoutMission} days";
            }

            $lowAssessment = AssessmentAttempt::query()
                ->where('user_id', $student->id)
                ->where('status', 'graded')
                ->where('accuracy', '<', $lowScore)
                ->exists();

            if ($lowAssessment) {
                $reasons[] = "Low assessment score (< {$lowScore}%)";
            }

            $recentEvidence = LearningEvidence::query()
                ->where('user_id', $student->id)
                ->where('created_at', '>=', now()->subDays(14))
                ->exists();

            if (! $recentEvidence) {
                $reasons[] = 'No recent learning evidence';
            }

            return [
                'student' => $student,
                'reasons' => $reasons,
                'at_risk' => count($reasons) > 0,
            ];
        })->filter(fn ($r) => $r['at_risk'])->values();
    }

    public function skillAnalytics(User $teacher): array
    {
        $memberIds = $this->memberIds($teacher);

        $skills = StudentSkill::query()
            ->select('skill_id', DB::raw('AVG(mastery) as avg_mastery'), DB::raw('COUNT(*) as learners'))
            ->with('skill')
            ->whereIn('user_id', $memberIds)
            ->groupBy('skill_id')
            ->orderByDesc('avg_mastery')
            ->get();

        $distribution = [
            'needs_support' => StudentSkill::query()->whereIn('user_id', $memberIds)->where('mastery', '<', 40)->count(),
            'developing' => StudentSkill::query()->whereIn('user_id', $memberIds)->whereBetween('mastery', [40, 59])->count(),
            'proficient' => StudentSkill::query()->whereIn('user_id', $memberIds)->whereBetween('mastery', [60, 79])->count(),
            'advanced' => StudentSkill::query()->whereIn('user_id', $memberIds)->where('mastery', '>=', 80)->count(),
        ];

        return [
            'average_mastery' => (int) round(StudentSkill::query()->whereIn('user_id', $memberIds)->avg('mastery') ?? 0),
            'skills' => $skills,
            'strongest' => $skills->take(3),
            'weakest' => $skills->sortBy('avg_mastery')->take(3)->values(),
            'distribution' => $distribution,
        ];
    }

    protected function ownedClassroomIds(User $teacher): Collection
    {
        return Classroom::query()->where('teacher_id', $teacher->id)->pluck('id');
    }

    protected function memberIds(User $teacher): Collection
    {
        return ClassroomMember::query()
            ->whereIn('classroom_id', $this->ownedClassroomIds($teacher))
            ->where('status', 'active')
            ->pluck('user_id')
            ->unique()
            ->values();
    }
}
