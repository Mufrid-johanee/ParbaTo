<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\HelpRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassTwinController extends Controller
{
    public function show(Request $request, ClassSession $session): View
    {
        $session->load([
            'classroom.course',
            'classroom.teacher',
            'classroom.members.user',
            'attendanceRecords',
            'helpRequests' => fn ($q) => $q->where('status', 'open'),
            'activities' => fn ($q) => $q->where('status', 'active'),
        ]);

        $presentIds = $session->attendanceRecords
            ->whereIn('status', ['present', 'late'])
            ->pluck('user_id')
            ->all();

        $userAttendance = $session->attendanceRecords
            ->firstWhere('user_id', $request->user()->id);

        return view('classtwin.live', compact('session', 'presentIds', 'userAttendance'));
    }

    public function checkIn(Request $request, ClassSession $session, AttendanceService $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $attendance->checkInWithCode(
            $session,
            $request->user(),
            $validated['code'],
            $request->ip()
        );

        return back()->with('status', 'Attendance recorded successfully.');
    }

    public function requestHelp(Request $request, ClassSession $session): RedirectResponse
    {
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
}
