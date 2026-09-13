<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\TeacherAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAnalyticsController extends Controller
{
    public function __construct(protected TeacherAnalyticsService $analytics) {}

    public function __invoke(Request $request): View
    {
        $teacher = $request->user();
        abort_unless($teacher->hasRole('teacher', 'admin'), 403);

        $overview = $this->analytics->overview($teacher);
        $classrooms = $this->analytics->classrooms($teacher);
        $missions = $this->analytics->missionAnalytics($teacher);
        $assessments = $this->analytics->assessmentAnalytics($teacher);
        $matrix = $this->analytics->progressMatrix($teacher);
        $atRisk = $this->analytics->atRisk($teacher);
        $skills = $this->analytics->skillAnalytics($teacher);

        $classroom = Classroom::query()->where('teacher_id', $teacher->id)->latest()->first();
        $classroomDetail = $classroom
            ? $this->analytics->classroomDetail($teacher, $classroom)
            : null;

        return view('analytics.teacher', compact(
            'overview',
            'classrooms',
            'missions',
            'assessments',
            'matrix',
            'atRisk',
            'skills',
            'classroom',
            'classroomDetail'
        ));
    }

    public function classroom(Request $request, Classroom $classroom): View
    {
        $teacher = $request->user();
        abort_unless($classroom->isOwnedBy($teacher), 403);

        $detail = $this->analytics->classroomDetail($teacher, $classroom);
        $matrix = $this->analytics->progressMatrix($teacher, $classroom);

        return view('analytics.classroom', [
            'detail' => $detail,
            'matrix' => $matrix,
        ]);
    }
}
