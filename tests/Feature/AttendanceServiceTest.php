<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_code_checks_in_member(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'CS-1',
            'title' => 'Test',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'Lab',
            'room_label' => 'R1',
        ]);
        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'desk_label' => 'D-01',
        ]);
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'started_by' => $teacher->id,
            'title' => 'Live',
            'status' => 'live',
            'started_at' => now(),
            'attendance_code' => hash('sha256', 'CODE1234'),
            'attendance_code_expires_at' => now()->addHour(),
        ]);

        $record = app(AttendanceService::class)->checkInWithCode($session, $student, 'CODE1234');

        $this->assertSame('present', $record->status);
        $this->assertDatabaseHas('attendance_records', [
            'class_session_id' => $session->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_invalid_code_is_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'CS-2',
            'title' => 'Test',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'Lab',
        ]);
        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
        ]);
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'started_by' => $teacher->id,
            'status' => 'live',
            'started_at' => now(),
            'attendance_code' => hash('sha256', 'CODE1234'),
            'attendance_code_expires_at' => now()->addHour(),
        ]);

        $this->expectException(ValidationException::class);
        app(AttendanceService::class)->checkInWithCode($session, $student, 'WRONG');
    }
}
