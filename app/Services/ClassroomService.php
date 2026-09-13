<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\User;
use App\Notifications\StudentJoinedClassroomNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassroomService
{
    public function create(User $teacher, array $data): Classroom
    {
        if (! $teacher->hasRole(User::ROLE_TEACHER, User::ROLE_ADMIN)) {
            throw ValidationException::withMessages([
                'classroom' => 'Only teachers can create classrooms.',
            ]);
        }

        return DB::transaction(function () use ($teacher, $data) {
            $courseId = $data['course_id'] ?? null;

            if (! $courseId) {
                $course = Course::query()->create([
                    'teacher_id' => $teacher->id,
                    'code' => $this->uniqueCourseCode($data['subject'] ?? $data['name']),
                    'title' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'term' => $data['term'] ?? null,
                    'status' => 'published',
                ]);
                $courseId = $course->id;
            } else {
                $course = Course::query()->findOrFail($courseId);
                if ($course->teacher_id !== $teacher->id && ! $teacher->hasRole(User::ROLE_ADMIN)) {
                    throw ValidationException::withMessages([
                        'course_id' => 'You can only attach classrooms to your own courses.',
                    ]);
                }
            }

            return Classroom::query()->create([
                'course_id' => $courseId,
                'teacher_id' => $teacher->id,
                'name' => $data['name'],
                'subject' => $data['subject'] ?? null,
                'description' => $data['description'] ?? null,
                'join_code' => $this->uniqueJoinCode(),
                'room_label' => $data['room_label'] ?? null,
                'capacity' => $data['capacity'] ?? 30,
                'rows' => $data['rows'] ?? 5,
                'cols' => $data['cols'] ?? 6,
                'status' => 'active',
            ]);
        });
    }

    public function update(Classroom $classroom, User $teacher, array $data): Classroom
    {
        if (! $classroom->isOwnedBy($teacher)) {
            abort(403, 'You cannot update this classroom.');
        }

        $classroom->forceFill([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'description' => $data['description'] ?? null,
            'room_label' => $data['room_label'] ?? null,
            'capacity' => $data['capacity'] ?? $classroom->capacity,
        ])->save();

        return $classroom->fresh();
    }

    public function archive(Classroom $classroom, User $teacher): Classroom
    {
        if (! $classroom->isOwnedBy($teacher)) {
            abort(403, 'You cannot archive this classroom.');
        }

        if ($classroom->liveSession()) {
            throw ValidationException::withMessages([
                'classroom' => 'End the live session before archiving this classroom.',
            ]);
        }

        $classroom->forceFill(['status' => 'archived'])->save();

        return $classroom->fresh();
    }

    public function removeMember(Classroom $classroom, User $teacher, User $student): ClassroomMember
    {
        if (! $classroom->isOwnedBy($teacher)) {
            abort(403, 'You cannot manage members of this classroom.');
        }

        $member = ClassroomMember::query()
            ->where('classroom_id', $classroom->id)
            ->where('user_id', $student->id)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'member' => 'That student is not in this classroom.',
            ]);
        }

        $member->forceFill(['status' => 'inactive'])->save();

        return $member->fresh();
    }

    public function joinByCode(User $student, string $plainCode): ClassroomMember
    {
        if (! $student->isStudent() && ! $student->hasRole(User::ROLE_ADMIN)) {
            throw ValidationException::withMessages([
                'code' => 'Only students can join classrooms with a join code.',
            ]);
        }

        $code = strtoupper(trim($plainCode));

        $classroom = Classroom::query()
            ->where('join_code', $code)
            ->where('status', 'active')
            ->first();

        if (! $classroom) {
            throw ValidationException::withMessages([
                'code' => 'Invalid classroom join code.',
            ]);
        }

        $existing = $classroom->members()->where('user_id', $student->id)->first();

        if ($existing) {
            if ($existing->status === 'active') {
                throw ValidationException::withMessages([
                    'code' => 'You are already a member of this classroom.',
                ]);
            }

            $existing->forceFill([
                'status' => 'active',
                'joined_at' => now(),
            ])->save();

            $this->notifyTeacherOfJoin($classroom, $student);

            return $existing->fresh();
        }

        $memberCount = $classroom->members()->where('status', 'active')->count();
        if ($memberCount >= $classroom->capacity) {
            throw ValidationException::withMessages([
                'code' => 'This classroom is at capacity.',
            ]);
        }

        $deskIndex = $memberCount + 1;

        $member = ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'desk_row' => (int) ceil($deskIndex / max(1, $classroom->cols)),
            'desk_col' => (($deskIndex - 1) % max(1, $classroom->cols)) + 1,
            'desk_label' => 'D-'.str_pad((string) $deskIndex, 2, '0', STR_PAD_LEFT),
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->notifyTeacherOfJoin($classroom, $student);

        return $member;
    }

    protected function notifyTeacherOfJoin(Classroom $classroom, User $student): void
    {
        $teacher = $classroom->teacher;
        if (! $teacher) {
            return;
        }

        $already = $teacher->notifications()
            ->where('type', StudentJoinedClassroomNotification::class)
            ->where('data->classroom_id', $classroom->id)
            ->where('data->student_id', $student->id)
            ->exists();

        if (! $already) {
            $teacher->notify(new StudentJoinedClassroomNotification($classroom, $student));
        }
    }

    public function uniqueJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Classroom::withTrashed()->where('join_code', $code)->exists());

        return $code;
    }

    protected function uniqueCourseCode(string $seed): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr(Str::slug($seed, ''), 0, 6)) ?: 'CLASS');
        $code = $base.'-'.strtoupper(Str::random(4));

        while (Course::withTrashed()->where('code', $code)->exists()) {
            $code = $base.'-'.strtoupper(Str::random(4));
        }

        return $code;
    }
}
