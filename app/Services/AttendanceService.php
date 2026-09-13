<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\ClassroomMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * Server-validated QR / code attendance. Students cannot invent presence.
     */
    public function checkInWithCode(ClassSession $session, User $student, string $plainCode, ?string $ip = null): AttendanceRecord
    {
        if (! $session->isLive()) {
            throw ValidationException::withMessages([
                'code' => 'Attendance is only available during a live ClassTwin session.',
            ]);
        }

        if (! $session->attendanceCodeMatches($plainCode)) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired attendance code.',
            ]);
        }

        $isMember = ClassroomMember::query()
            ->where('classroom_id', $session->classroom_id)
            ->where('user_id', $student->id)
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'code' => 'You are not a member of this classroom.',
            ]);
        }

        return DB::transaction(function () use ($session, $student, $ip) {
            return AttendanceRecord::query()->firstOrCreate(
                [
                    'class_session_id' => $session->id,
                    'user_id' => $student->id,
                ],
                [
                    'method' => 'qr',
                    'status' => 'present',
                    'ip_address' => $ip,
                    'checked_in_at' => now(),
                ]
            );
        });
    }

    public function markManual(ClassSession $session, User $student, User $teacher, string $status = 'present'): AttendanceRecord
    {
        if (! $teacher->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN)) {
            throw ValidationException::withMessages([
                'attendance' => 'Only teachers can mark manual attendance.',
            ]);
        }

        return AttendanceRecord::query()->updateOrCreate(
            [
                'class_session_id' => $session->id,
                'user_id' => $student->id,
            ],
            [
                'method' => 'manual',
                'status' => $status,
                'marked_by' => $teacher->id,
                'checked_in_at' => now(),
            ]
        );
    }
}
