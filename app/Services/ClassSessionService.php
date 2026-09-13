<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\User;
use App\Notifications\SessionStartedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassSessionService
{
    public const PRESENCE_ACTIVE_SECONDS = 120;

    public function __construct(
        protected AttendanceService $attendance
    ) {}

    /**
     * @return array{session: ClassSession, plain_code: string}
     */
    public function start(Classroom $classroom, User $teacher, array $data = []): array
    {
        $this->assertOwnsClassroom($classroom, $teacher);

        if ($classroom->liveSession()) {
            throw ValidationException::withMessages([
                'session' => 'This classroom already has a live session. End it before starting another.',
            ]);
        }

        return DB::transaction(function () use ($classroom, $teacher, $data) {
            $session = ClassSession::query()->create([
                'classroom_id' => $classroom->id,
                'started_by' => $teacher->id,
                'title' => $data['title'] ?? ($classroom->name.' — '.now()->format('M j, g:i A')),
                'status' => 'live',
                'started_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $plainCode = $session->rotateAttendanceCode((int) ($data['code_ttl_minutes'] ?? 60));
            $this->rememberPlainCode($session, $plainCode);

            $session = $session->fresh(['classroom']);
            $this->notifyMembersSessionStarted($session);

            return ['session' => $session, 'plain_code' => $plainCode];
        });
    }

    protected function notifyMembersSessionStarted(ClassSession $session): void
    {
        $members = User::query()
            ->whereIn(
                'id',
                ClassroomMember::query()
                    ->where('classroom_id', $session->classroom_id)
                    ->where('status', 'active')
                    ->pluck('user_id')
            )
            ->get();

        foreach ($members as $member) {
            $already = $member->notifications()
                ->where('type', SessionStartedNotification::class)
                ->where('data->session_id', $session->id)
                ->exists();

            if (! $already) {
                $member->notify(new SessionStartedNotification($session));
            }
        }
    }

    /**
     * @return array{session: ClassSession, plain_code: string}
     */
    public function rotateCode(ClassSession $session, User $teacher, int $ttlMinutes = 60): array
    {
        $this->assertOwnsClassroom($session->classroom, $teacher);

        if (! $session->isLive()) {
            throw ValidationException::withMessages([
                'session' => 'Attendance codes can only be rotated on live sessions.',
            ]);
        }

        $plainCode = $session->rotateAttendanceCode($ttlMinutes);
        $this->rememberPlainCode($session, $plainCode);

        return ['session' => $session->fresh(), 'plain_code' => $plainCode];
    }

    public function end(ClassSession $session, User $teacher): ClassSession
    {
        $this->assertOwnsClassroom($session->classroom, $teacher);

        if ($session->status === 'ended') {
            return $session;
        }

        if (! $session->isLive() && $session->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'session' => 'Only live or scheduled sessions can be ended.',
            ]);
        }

        return DB::transaction(function () use ($session) {
            $memberCount = ClassroomMember::query()
                ->where('classroom_id', $session->classroom_id)
                ->where('status', 'active')
                ->count();

            $presentCount = AttendanceRecord::query()
                ->where('class_session_id', $session->id)
                ->whereIn('status', ['present', 'late'])
                ->count();

            $session->forceFill([
                'status' => 'ended',
                'ended_at' => now(),
                'member_count_snapshot' => $memberCount,
                'present_count_snapshot' => $presentCount,
                'attendance_code' => null,
                'attendance_code_expires_at' => null,
            ])->save();

            Cache::forget($this->codeCacheKey($session));

            return $session->fresh();
        });
    }

    public function joinSession(ClassSession $session, User $student): AttendanceRecord
    {
        if (! $session->isLive()) {
            throw ValidationException::withMessages([
                'session' => 'This session is not live.',
            ]);
        }

        $this->assertMembership($session, $student);

        return DB::transaction(function () use ($session, $student) {
            $record = AttendanceRecord::query()->firstOrCreate(
                [
                    'class_session_id' => $session->id,
                    'user_id' => $student->id,
                ],
                [
                    'method' => 'system',
                    'status' => 'present',
                    'checked_in_at' => now(),
                    'last_seen_at' => now(),
                ]
            );

            if ($record->wasRecentlyCreated === false) {
                $record->forceFill(['last_seen_at' => now()])->save();
            }

            return $record->fresh();
        });
    }

    public function heartbeat(ClassSession $session, User $student): AttendanceRecord
    {
        if (! $session->isLive()) {
            throw ValidationException::withMessages([
                'session' => 'Presence updates are only available during live sessions.',
            ]);
        }

        $this->assertMembership($session, $student);

        $record = AttendanceRecord::query()
            ->where('class_session_id', $session->id)
            ->where('user_id', $student->id)
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'presence' => 'Check in or join the session before sending presence heartbeats.',
            ]);
        }

        $record->forceFill(['last_seen_at' => now()])->save();

        return $record->fresh();
    }

    public function plainCodeFor(ClassSession $session): ?string
    {
        return Cache::get($this->codeCacheKey($session));
    }

    /**
     * Presence labels derived from attendance + last_seen_at (not fake analytics).
     *
     * @return list<array{user_id:int,name:string,desk_label:?string,attendance:?string,presence:string,last_seen_at:?string}>
     */
    public function presenceMap(ClassSession $session): array
    {
        $session->loadMissing(['classroom.members.user', 'attendanceRecords']);

        $attendanceByUser = $session->attendanceRecords->keyBy('user_id');

        return $session->classroom->members
            ->where('status', 'active')
            ->map(function (ClassroomMember $member) use ($attendanceByUser) {
                $record = $attendanceByUser->get($member->user_id);
                $presence = 'absent';

                if ($record && in_array($record->status, ['present', 'late'], true)) {
                    $lastSeen = $record->last_seen_at ?? $record->checked_in_at;
                    if ($lastSeen && $lastSeen->gt(now()->subSeconds(self::PRESENCE_ACTIVE_SECONDS))) {
                        $presence = 'active';
                    } else {
                        $presence = 'idle';
                    }
                }

                return [
                    'user_id' => $member->user_id,
                    'name' => $member->user->preferredName(),
                    'desk_label' => $member->desk_label,
                    'attendance' => $record?->status,
                    'presence' => $presence,
                    'last_seen_at' => ($record?->last_seen_at ?? $record?->checked_in_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function historyStats(ClassSession $session): array
    {
        $members = $session->member_count_snapshot
            ?? ClassroomMember::query()->where('classroom_id', $session->classroom_id)->count();
        $present = $session->present_count_snapshot
            ?? AttendanceRecord::query()
                ->where('class_session_id', $session->id)
                ->whereIn('status', ['present', 'late'])
                ->count();

        $durationMinutes = null;
        if ($session->started_at && $session->ended_at) {
            $durationMinutes = $session->started_at->diffInMinutes($session->ended_at);
        } elseif ($session->started_at && $session->isLive()) {
            $durationMinutes = $session->started_at->diffInMinutes(now());
        }

        return [
            'member_count' => (int) $members,
            'present_count' => (int) $present,
            'attendance_percent' => $members > 0 ? (int) round(($present / $members) * 100) : 0,
            'duration_minutes' => $durationMinutes,
        ];
    }

    protected function rememberPlainCode(ClassSession $session, string $plainCode): void
    {
        $ttl = $session->attendance_code_expires_at
            ? now()->diffInSeconds($session->attendance_code_expires_at, false)
            : 3600;

        Cache::put($this->codeCacheKey($session), $plainCode, max(60, (int) $ttl));
    }

    protected function codeCacheKey(ClassSession $session): string
    {
        return 'classtwin.session.'.$session->id.'.attendance_code';
    }

    protected function assertOwnsClassroom(Classroom $classroom, User $teacher): void
    {
        if ($teacher->hasRole(User::ROLE_ADMIN)) {
            return;
        }

        if (! $teacher->hasRole(User::ROLE_TEACHER) || $classroom->teacher_id !== $teacher->id) {
            abort(403, 'You are not authorized to manage this classroom session.');
        }
    }

    protected function assertMembership(ClassSession $session, User $student): void
    {
        $isMember = ClassroomMember::query()
            ->where('classroom_id', $session->classroom_id)
            ->where('user_id', $student->id)
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'session' => 'You are not a member of this classroom.',
            ]);
        }
    }
}
