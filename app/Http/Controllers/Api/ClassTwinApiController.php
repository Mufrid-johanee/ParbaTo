<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JoinClassroomRequest;
use App\Http\Requests\StartClassSessionRequest;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ClassroomService;
use App\Services\ClassSessionService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassTwinApiController extends Controller
{
    public function classrooms(Request $request): JsonResponse
    {
        $user = $request->user();

        $owned = $user->hasRole('teacher', 'admin')
            ? Classroom::query()->withCount('members')->where('teacher_id', $user->id)->latest()->get()
            : collect();

        $joined = Classroom::query()
            ->withCount('members')
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'owned' => $owned,
                'joined' => $joined,
            ],
        ]);
    }

    public function storeClassroom(StoreClassroomRequest $request, ClassroomService $classrooms): JsonResponse
    {
        $classroom = $classrooms->create($request->user(), $request->validated());

        return response()->json(['data' => $classroom], 201);
    }

    public function updateClassroom(UpdateClassroomRequest $request, Classroom $classroom, ClassroomService $classrooms): JsonResponse
    {
        $updated = $classrooms->update($classroom, $request->user(), $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function archiveClassroom(Request $request, Classroom $classroom, ClassroomService $classrooms): JsonResponse
    {
        $this->authorize('update', $classroom);
        $archived = $classrooms->archive($classroom, $request->user());

        return response()->json(['data' => $archived]);
    }

    public function removeMember(Request $request, Classroom $classroom, User $student, ClassroomService $classrooms): JsonResponse
    {
        $this->authorize('removeMember', $classroom);
        $member = $classrooms->removeMember($classroom, $request->user(), $student);

        return response()->json(['data' => $member]);
    }

    public function showClassroom(Request $request, Classroom $classroom): JsonResponse
    {
        $this->authorize('view', $classroom);
        $classroom->load(['course', 'teacher', 'members' => fn ($q) => $q->where('status', 'active')->with('user')]);

        return response()->json(['data' => $classroom]);
    }

    public function joinClassroom(JoinClassroomRequest $request, ClassroomService $classrooms): JsonResponse
    {
        $member = $classrooms->joinByCode($request->user(), $request->validated('code'));

        return response()->json(['data' => $member->load('classroom')], 201);
    }

    public function startSession(
        StartClassSessionRequest $request,
        Classroom $classroom,
        ClassSessionService $sessions
    ): JsonResponse {
        $result = $sessions->start($classroom, $request->user(), $request->validated());

        return response()->json([
            'data' => [
                'session' => $result['session'],
                'attendance_code' => $result['plain_code'],
            ],
        ], 201);
    }

    public function showSession(
        Request $request,
        ClassSession $session,
        ClassSessionService $sessions,
        QrCodeService $qr
    ): JsonResponse {
        $this->authorize('view', $session);

        $plain = $session->classroom->isOwnedBy($request->user())
            ? $sessions->plainCodeFor($session)
            : null;

        return response()->json([
            'data' => [
                'session' => $session->load(['classroom.teacher']),
                'presence' => $sessions->presenceMap($session),
                'stats' => $sessions->historyStats($session),
                'attendance_code' => $plain,
                'qr_svg' => $plain ? $qr->svg('PARBATO-ATTEND:'.$plain, 180) : null,
            ],
        ]);
    }

    public function joinSession(Request $request, ClassSession $session, ClassSessionService $sessions): JsonResponse
    {
        $this->authorize('attend', $session);
        $record = $sessions->joinSession($session, $request->user());

        return response()->json(['data' => $record]);
    }

    public function attendance(
        Request $request,
        ClassSession $session,
        AttendanceService $attendance
    ): JsonResponse {
        $this->authorize('attend', $session);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $code = preg_replace('/^PARBATO-ATTEND:/i', '', trim($validated['code']));

        $record = $attendance->checkInWithCode(
            $session,
            $request->user(),
            $code,
            $request->ip()
        );

        return response()->json(['data' => $record]);
    }

    public function heartbeat(Request $request, ClassSession $session, ClassSessionService $sessions): JsonResponse
    {
        $this->authorize('attend', $session);
        $record = $sessions->heartbeat($session, $request->user());

        return response()->json(['data' => $record]);
    }

    public function endSession(Request $request, ClassSession $session, ClassSessionService $sessions): JsonResponse
    {
        $this->authorize('end', $session);
        $ended = $sessions->end($session, $request->user());

        return response()->json([
            'data' => [
                'session' => $ended,
                'stats' => $sessions->historyStats($ended),
            ],
        ]);
    }

    public function classroomSessions(Request $request, Classroom $classroom, ClassSessionService $sessions): JsonResponse
    {
        $this->authorize('view', $classroom);

        $items = $classroom->sessions()->latest()->get()->map(fn (ClassSession $s) => [
            'session' => $s,
            'stats' => $sessions->historyStats($s),
        ]);

        return response()->json(['data' => $items]);
    }

    public function markAttendance(
        Request $request,
        ClassSession $session,
        AttendanceService $attendance
    ): JsonResponse {
        $this->authorize('manage', $session);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'in:present,late,absent,excused'],
        ]);

        $student = User::query()->findOrFail($validated['user_id']);
        $record = $attendance->markManual($session, $student, $request->user(), $validated['status']);

        return response()->json(['data' => $record]);
    }
}
