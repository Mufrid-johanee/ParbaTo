<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Services\TeacherAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2PortfolioAnalyticsNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_ownership_and_teacher_access(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'PORT-1',
            'title' => 'Port Course',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'Port Lab',
            'join_code' => 'PORTFOL1',
            'capacity' => 30,
            'rows' => 4,
            'cols' => 6,
            'status' => 'active',
        ]);
        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'status' => 'active',
            'joined_at' => now(),
            'desk_row' => 1,
            'desk_col' => 1,
            'desk_label' => 'D-01',
        ]);

        PortfolioItem::query()->create([
            'user_id' => $student->id,
            'source_type' => AssessmentAttempt::class,
            'source_id' => 1,
            'title' => 'Quiz result',
            'summary' => '90%',
            'evidence' => ['type' => 'assessment'],
        ]);

        $this->actingAs($student)->get(route('student.profile'))->assertOk()->assertSee('Quiz result');
        $this->actingAs($other)->get(route('teacher.students.portfolio', $student))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.students.portfolio', $student))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.students.portfolio', $other))->assertForbidden();
    }

    public function test_analytics_scoped_to_teacher_and_at_risk_has_reasons(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $classroom = Classroom::query()->create([
            'course_id' => Course::query()->create([
                'teacher_id' => $teacher->id,
                'code' => 'AN-1',
                'title' => 'Analytics Course',
                'status' => 'published',
            ])->id,
            'teacher_id' => $teacher->id,
            'name' => 'Analytics Lab',
            'join_code' => 'ANALYT01',
            'capacity' => 20,
            'rows' => 4,
            'cols' => 5,
            'status' => 'active',
        ]);
        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'status' => 'active',
            'joined_at' => now(),
            'desk_row' => 1,
            'desk_col' => 1,
            'desk_label' => 'D-01',
        ]);

        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'started_by' => $teacher->id,
            'title' => 'S1',
            'status' => 'ended',
            'started_at' => now()->subDay(),
            'ended_at' => now()->subDay()->addHour(),
        ]);
        AttendanceRecord::query()->create([
            'class_session_id' => $session->id,
            'user_id' => $student->id,
            'method' => 'manual',
            'status' => 'absent',
            'checked_in_at' => now()->subDay(),
        ]);

        $overview = app(TeacherAnalyticsService::class)->overview($teacher);
        $this->assertSame(1, $overview['total_students']);
        $this->assertSame(1, $overview['total_classrooms']);

        $leaked = app(TeacherAnalyticsService::class)->overview($otherTeacher);
        $this->assertSame(0, $leaked['total_students']);

        $this->actingAs($otherTeacher)
            ->getJson('/api/analytics/overview')
            ->assertOk()
            ->assertJsonPath('total_students', 0);

        $this->actingAs($teacher)->get(route('analytics.teacher'))->assertOk()->assertSee('Needs attention');

        $atRisk = app(TeacherAnalyticsService::class)->atRisk($teacher);
        $this->assertTrue($atRisk->isNotEmpty());
        $this->assertNotEmpty($atRisk->first()['reasons']);
    }

    public function test_notifications_mark_read_and_authorization(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $assessment = Assessment::query()->create([
            'created_by' => User::factory()->teacher()->create()->id,
            'title' => 'N1',
            'type' => 'quiz',
            'status' => 'published',
            'pass_score' => 60,
            'max_attempts' => 1,
        ]);
        $student->notify(new \App\Notifications\AssessmentPublishedNotification($assessment));

        $this->actingAs($student)->get(route('notifications.index'))->assertOk();
        $this->actingAs($student)->getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('count', 1);

        $id = $student->notifications()->first()->id;
        $this->actingAs($other)->postJson('/api/notifications/'.$id.'/read')->assertNotFound();
        $this->actingAs($student)->postJson('/api/notifications/'.$id.'/read')->assertOk();
        $this->actingAs($student)->getJson('/api/notifications/unread-count')->assertJsonPath('count', 0);

        $student->notify(new \App\Notifications\AssessmentPublishedNotification($assessment));
        $this->actingAs($student)->postJson('/api/notifications/read-all')->assertOk();
        $this->assertSame(0, $student->fresh()->unreadNotifications()->count());
    }
}
