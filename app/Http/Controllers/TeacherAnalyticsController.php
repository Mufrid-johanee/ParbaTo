<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\HelpRequest;
use App\Models\MissionEnrollment;
use App\Models\StudentSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherAnalyticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user();

        $classroom = Classroom::query()
            ->with(['course', 'members.user'])
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->first();

        $insights = [];
        $topicDifficulty = collect();
        $studentsNeedingSupport = collect();
        $missionStats = collect();
        $presentCount = 0;
        $memberCount = 0;
        $avgMastery = 0;
        $missionCompletion = 0;

        if ($classroom) {
            $memberIds = $classroom->members->pluck('user_id');
            $memberCount = $memberIds->count();

            $liveSession = $classroom->liveSession();
            if ($liveSession) {
                $presentCount = AttendanceRecord::query()
                    ->where('class_session_id', $liveSession->id)
                    ->whereIn('status', ['present', 'late'])
                    ->count();
            }

            $topicDifficulty = StudentSkill::query()
                ->select('skill_id', DB::raw('AVG(mastery) as avg_mastery'), DB::raw('COUNT(*) as learners'))
                ->with('skill')
                ->whereIn('user_id', $memberIds)
                ->groupBy('skill_id')
                ->orderBy('avg_mastery')
                ->get();

            if ($topicDifficulty->isNotEmpty()) {
                $hardest = $topicDifficulty->first();
                $insights[] = sprintf(
                    '%s is currently the highest difficulty topic (avg mastery %d%%).',
                    $hardest->skill->name,
                    (int) round($hardest->avg_mastery)
                );
            }

            $studentsNeedingSupport = StudentSkill::query()
                ->with(['user', 'skill'])
                ->whereIn('user_id', $memberIds)
                ->where('mastery', '<', 55)
                ->orderBy('mastery')
                ->get()
                ->groupBy('user_id')
                ->map(fn ($rows) => $rows->first())
                ->values();

            if ($studentsNeedingSupport->isNotEmpty()) {
                $insights[] = sprintf(
                    '%d student(s) may need additional support based on skill mastery below 55%%.',
                    $studentsNeedingSupport->count()
                );
            }

            $openHelp = HelpRequest::query()
                ->whereHas('session', fn ($q) => $q->where('classroom_id', $classroom->id)->where('status', 'live'))
                ->where('status', 'open')
                ->count();

            if ($openHelp > 0) {
                $insights[] = sprintf('%d open help request(s) in the live ClassTwin session.', $openHelp);
            }

            $avgMastery = (int) round(
                StudentSkill::query()->whereIn('user_id', $memberIds)->avg('mastery') ?? 0
            );

            $missionStats = MissionEnrollment::query()
                ->select('mission_id', DB::raw('COUNT(*) as attempts'), DB::raw("SUM(status = 'completed') as completed"))
                ->with('mission')
                ->whereIn('user_id', $memberIds)
                ->groupBy('mission_id')
                ->get();

            $totalAttempts = $missionStats->sum('attempts');
            $totalCompleted = $missionStats->sum('completed');
            $missionCompletion = $totalAttempts > 0 ? (int) round(($totalCompleted / $totalAttempts) * 100) : 0;

            if ($missionCompletion > 0) {
                $insights[] = sprintf('Mission completion across enrolled learners is %d%%.', $missionCompletion);
            }
        }

        if (empty($insights)) {
            $insights[] = 'Not enough stored learning evidence yet to produce deeper insights.';
        }

        return view('analytics.teacher', compact(
            'classroom',
            'insights',
            'topicDifficulty',
            'studentsNeedingSupport',
            'missionStats',
            'presentCount',
            'memberCount',
            'avgMastery',
            'missionCompletion'
        ));
    }
}
