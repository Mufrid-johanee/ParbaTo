<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Services\TeacherAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherAnalyticsApiController extends Controller
{
    public function __construct(protected TeacherAnalyticsService $analytics) {}

    protected function teacher(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('teacher', 'admin'), 403);

        return $user;
    }

    public function overview(Request $request): JsonResponse
    {
        return response()->json($this->analytics->overview($this->teacher($request)));
    }

    public function classrooms(Request $request): JsonResponse
    {
        return response()->json($this->analytics->classrooms($this->teacher($request)));
    }

    public function classroom(Request $request, Classroom $classroom): JsonResponse
    {
        return response()->json($this->analytics->classroomDetail($this->teacher($request), $classroom));
    }

    public function missions(Request $request): JsonResponse
    {
        return response()->json($this->analytics->missionAnalytics($this->teacher($request)));
    }

    public function assessments(Request $request): JsonResponse
    {
        return response()->json($this->analytics->assessmentAnalytics($this->teacher($request)));
    }

    public function atRisk(Request $request): JsonResponse
    {
        return response()->json($this->analytics->atRisk($this->teacher($request)));
    }

    public function skills(Request $request): JsonResponse
    {
        return response()->json($this->analytics->skillAnalytics($this->teacher($request)));
    }
}
