<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ClassroomService;
use App\Services\ClassSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassTwinMvpTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $course = Course::query()->create([
            'teacher_id' => $this->teacher->id,
            'code' => 'CS-CT',
            'title' => 'ClassTwin Course',
            'status' => 'published',
        ]);

        $this->classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Lab Twin',
            'subject' => 'Web',
            'join_code' => 'JOINCODE1',
            'room_label' => 'R1',
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_create_classroom(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('classrooms.store'), [
                'name' => 'New Twin Room',
                'subject' => 'Networks',
                'description' => 'Week lab twin',
                'room_label' => 'Lab 2',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('classrooms', [
            'name' => 'New Twin Room',
            'teacher_id' => $this->teacher->id,
            'subject' => 'Networks',
        ]);

        $created = Classroom::query()->where('name', 'New Twin Room')->first();
        $this->assertNotNull($created->join_code);
        $this->assertSame(8, strlen($created->join_code));
    }

    public function test_student_can_join_valid_classroom(): void
    {
        $this->actingAs($this->student)
            ->post(route('classrooms.join.store'), ['code' => 'JOINCODE1'])
            ->assertRedirect(route('classrooms.show', $this->classroom));

        $this->assertDatabaseHas('classroom_members', [
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'status' => 'active',
        ]);
    }

    public function test_duplicate_membership_prevented(): void
    {
        app(ClassroomService::class)->joinByCode($this->student, 'JOINCODE1');

        $this->expectException(ValidationException::class);
        app(ClassroomService::class)->joinByCode($this->student, 'JOINCODE1');
    }

    public function test_other_teacher_cannot_start_session(): void
    {
        $intruder = User::factory()->teacher()->create();

        $this->actingAs($intruder)
            ->post(route('classrooms.sessions.start', $this->classroom), [
                'title' => 'Unauthorized',
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_start_and_end_session(): void
    {
        ClassroomMember::query()->create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($this->teacher)
            ->post(route('classrooms.sessions.start', $this->classroom), [
                'title' => 'Morning Lab',
            ])
            ->assertRedirect();

        $session = ClassSession::query()->where('classroom_id', $this->classroom->id)->first();
        $this->assertSame('live', $session->status);
        $this->assertNotNull($session->attendance_code);

        $this->actingAs($this->student)
            ->post(route('classtwin.end', $session))
            ->assertForbidden();

        $this->actingAs($this->teacher)
            ->post(route('classtwin.end', $session))
            ->assertRedirect(route('classrooms.show', $this->classroom));

        $session->refresh();
        $this->assertSame('ended', $session->status);
        $this->assertNotNull($session->ended_at);
        $this->assertSame(1, $session->member_count_snapshot);
    }

    public function test_student_attendance_and_duplicate_prevention(): void
    {
        ClassroomMember::query()->create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $result = app(ClassSessionService::class)->start($this->classroom, $this->teacher, [
            'title' => 'Attendance Lab',
        ]);
        $session = $result['session'];
        $code = $result['plain_code'];

        $this->actingAs($this->student)
            ->post(route('classtwin.attendance', $session), ['code' => $code])
            ->assertRedirect();

        $this->assertDatabaseCount('attendance_records', 1);

        // Second check-in is idempotent
        $this->actingAs($this->student)
            ->post(route('classtwin.attendance', $session), ['code' => $code])
            ->assertRedirect();

        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_invalid_code_and_closed_session_rejected(): void
    {
        ClassroomMember::query()->create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $result = app(ClassSessionService::class)->start($this->classroom, $this->teacher, []);
        $session = $result['session'];

        $this->actingAs($this->student)
            ->post(route('classtwin.attendance', $session), ['code' => 'WRONGCODE'])
            ->assertSessionHasErrors('code');

        app(ClassSessionService::class)->end($session, $this->teacher);

        $this->actingAs($this->student)
            ->post(route('classtwin.attendance', $session->fresh()), ['code' => $result['plain_code']])
            ->assertSessionHasErrors('code');
    }

    public function test_non_member_cannot_attend(): void
    {
        $outsider = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $result = app(ClassSessionService::class)->start($this->classroom, $this->teacher, []);

        $this->actingAs($outsider)
            ->post(route('classtwin.attendance', $result['session']), ['code' => $result['plain_code']])
            ->assertForbidden();
    }

    public function test_heartbeat_updates_presence(): void
    {
        ClassroomMember::query()->create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $result = app(ClassSessionService::class)->start($this->classroom, $this->teacher, []);
        $session = $result['session'];

        app(AttendanceService::class)->checkInWithCode($session, $this->student, $result['plain_code']);

        $this->actingAs($this->student)
            ->postJson(route('classtwin.heartbeat', $session))
            ->assertOk()
            ->assertJsonPath('data.status', 'present');

        $record = AttendanceRecord::query()->where('user_id', $this->student->id)->first();
        $this->assertNotNull($record->last_seen_at);

        $map = app(ClassSessionService::class)->presenceMap($session);
        $row = collect($map)->firstWhere('user_id', $this->student->id);
        $this->assertSame('active', $row['presence']);
    }

    public function test_student_cannot_view_other_classroom(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($other)
            ->get(route('classrooms.show', $this->classroom))
            ->assertForbidden();
    }

    public function test_api_start_session_returns_code_to_teacher_only(): void
    {
        ClassroomMember::query()->create([
            'classroom_id' => $this->classroom->id,
            'user_id' => $this->student->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($this->teacher)
            ->postJson('/api/classrooms/'.$this->classroom->id.'/sessions', [
                'title' => 'API Live',
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['session', 'attendance_code']]);

        $sessionId = ClassSession::query()->latest('id')->value('id');

        $this->actingAs($this->student)
            ->getJson('/api/sessions/'.$sessionId)
            ->assertOk()
            ->assertJsonPath('data.attendance_code', null);
    }
}
