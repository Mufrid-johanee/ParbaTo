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
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'code' => 'You are not a member of this classroom.',
            ]);
        }

        return DB::transaction(function () use ($session, $student, $ip) {
            $record = AttendanceRecord::query()->firstOrCreate(
                [
                    'class_session_id' => $session->id,
                    'user_id' => $student->id,
                ],
                [
                    'method' => 'qr',
                    'status' => 'present',
                    'ip_address' => $ip,
                    'checked_in_at' => now(),
                    'last_seen_at' => now(),
                ]
            );

            if (! $record->wasRecentlyCreated) {
                $record->forceFill([
                    'last_seen_at' => now(),
                    'ip_address' => $ip ?? $record->ip_address,
                ])->save();
            } else {
                app(XpService::class)->award(
                    $student,
                    'classtwin_attendance',
                    $record->id,
                    'ClassTwin attendance'
                );
                app(AuditLogService::class)->log('attendance_marked', $student, $record, [
                    'method' => 'qr',
                    'session_id' => $session->id,
                ]);
            }

            return $record->fresh();
        });
    }

    public function markManual(ClassSession $session, User $student, User $teacher, string $status = 'present'): AttendanceRecord
    {
        if (! $teacher->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN)) {
            throw ValidationException::withMessages([
                'attendance' => 'Only teachers can mark manual attendance.',
            ]);
        }

        $session->loadMissing('classroom');

        if ($teacher->hasRole(User::ROLE_TEACHER)
            && ! $teacher->hasRole(User::ROLE_ADMIN)
            && $session->classroom->teacher_id !== $teacher->id) {
            abort(403, 'You can only mark attendance for your own classrooms.');
        }

        $isMember = ClassroomMember::query()
            ->where('classroom_id', $session->classroom_id)
            ->where('user_id', $student->id)
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'attendance' => 'That student is not a member of this classroom.',
            ]);
        }

        if (! $session->isLive()) {
            throw ValidationException::withMessages([
                'attendance' => 'Manual attendance is only available during live sessions.',
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
                'last_seen_at' => now(),
            ]
        );
    }
}
