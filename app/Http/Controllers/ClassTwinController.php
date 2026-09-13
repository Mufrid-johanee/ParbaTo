<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\HelpRequest;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ClassSessionService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassTwinController extends Controller
{
    public function show(
        Request $request,
        ClassSession $session,
        ClassSessionService $sessions,
        QrCodeService $qr
    ): View {
        $this->authorize('view', $session);

        $session->load([
            'classroom.course',
            'classroom.teacher',
            'classroom.members' => fn ($q) => $q->where('status', 'active')->with('user'),
            'attendanceRecords',
            'helpRequests' => fn ($q) => $q->where('status', 'open')->with('user'),
            'activities' => fn ($q) => $q->where('status', 'active'),
        ]);

        $presence = $sessions->presenceMap($session);
        $presentIds = collect($presence)
            ->whereIn('presence', ['active', 'idle'])
            ->pluck('user_id')
            ->all();

        $userAttendance = $session->attendanceRecords
            ->firstWhere('user_id', $request->user()->id);

        $isTeacher = $session->classroom->isOwnedBy($request->user());
        $plainCode = $isTeacher
            ? ($sessions->plainCodeFor($session) ?? session('attendance_code'))
            : null;
        $stats = $sessions->historyStats($session);
        $qrSvg = ($isTeacher && $plainCode)
            ? $qr->svg('PARBATO-ATTEND:'.$plainCode, 200)
            : null;

        return view('classtwin.live', compact(
            'session',
            'presentIds',
            'userAttendance',
            'presence',
            'isTeacher',
            'plainCode',
            'stats',
            'qrSvg'
        ));
    }

    public function checkIn(Request $request, ClassSession $session, AttendanceService $attendance): RedirectResponse
    {
        $this->authorize('attend', $session);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $code = preg_replace('/^PARBATO-ATTEND:/i', '', trim($validated['code']));

        $attendance->checkInWithCode(
            $session,
            $request->user(),
            $code,
            $request->ip()
        );

        return back()->with('status', 'Attendance recorded successfully.');
    }

    public function joinSession(Request $request, ClassSession $session, ClassSessionService $sessions): RedirectResponse
    {
        $this->authorize('attend', $session);

        $sessions->joinSession($session, $request->user());

        return back()->with('status', 'You joined the live session. Presence is now active.');
    }

    public function heartbeat(Request $request, ClassSession $session, ClassSessionService $sessions): JsonResponse
    {
        $this->authorize('attend', $session);

        $record = $sessions->heartbeat($session, $request->user());

        return response()->json([
            'data' => [
                'last_seen_at' => $record->last_seen_at?->toIso8601String(),
                'status' => $record->status,
            ],
        ]);
    }

    public function presence(Request $request, ClassSession $session, ClassSessionService $sessions): JsonResponse
    {
        $this->authorize('view', $session);

        return response()->json([
            'data' => [
                'session_status' => $session->status,
                'presence' => $sessions->presenceMap($session),
                'stats' => $sessions->historyStats($session),
                'help_open' => $session->helpRequests()->where('status', 'open')->count(),
            ],
        ]);
    }

    public function rotateCode(Request $request, ClassSession $session, ClassSessionService $sessions): RedirectResponse
    {
        $this->authorize('manage', $session);

        $result = $sessions->rotateCode($session, $request->user());

        return back()
            ->with('status', 'Attendance code rotated.')
            ->with('attendance_code', $result['plain_code']);
    }

    public function end(Request $request, ClassSession $session, ClassSessionService $sessions): RedirectResponse
    {
        $this->authorize('end', $session);

        $sessions->end($session, $request->user());

        return redirect()
            ->route('classrooms.show', $session->classroom_id)
            ->with('status', 'Session ended. History snapshot saved.');
    }

    public function markAttendance(
        Request $request,
        ClassSession $session,
        AttendanceService $attendance
    ): RedirectResponse {
        $this->authorize('manage', $session);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'in:present,late,absent,excused'],
        ]);

        $student = User::query()->findOrFail($validated['user_id']);
        $attendance->markManual($session, $student, $request->user(), $validated['status']);

        return back()->with('status', 'Manual attendance updated for '.$student->preferredName().'.');
    }

    public function requestHelp(Request $request, ClassSession $session): RedirectResponse
    {
        $this->authorize('attend', $session);
        abort_unless($session->isLive(), 422);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        HelpRequest::query()->create([
            'class_session_id' => $session->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'] ?? null,
            'status' => 'open',
        ]);

        return back()->with('status', 'Help request sent to the instructor.');
    }

    public function resolveHelp(Request $request, ClassSession $session, HelpRequest $help): RedirectResponse
    {
        $this->authorize('manage', $session);
        abort_unless($help->class_session_id === $session->id, 404);

        $help->forceFill([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ])->save();

        return back()->with('status', 'Help request resolved.');
    }
}
