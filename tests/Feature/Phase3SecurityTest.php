<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Phase3SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_present(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertNotEmpty($csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $csp);
    }

    public function test_login_throttle_and_audit_log(): void
    {
        $user = User::factory()->create([
            'email' => 'throttle@example.com',
            'role' => User::ROLE_STUDENT,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'throttle@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => 'throttle@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_failure',
        ]);

        RateLimiter::clear('login:throttle@example.com|127.0.0.1');

        $this->post('/login', [
            'email' => 'throttle@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_success',
            'user_id' => $user->id,
        ]);
    }

    public function test_student_cannot_access_admin_or_other_attempt(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->get(route('admin.index'))->assertForbidden();
        $this->actingAs($student)->get(route('analytics.teacher'))->assertForbidden();

        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'SEC-1',
            'title' => 'Sec',
            'status' => 'published',
        ]);
        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'Secret',
            'type' => 'quiz',
            'status' => 'published',
            'pass_score' => 60,
            'max_attempts' => 1,
        ]);
        $attempt = AssessmentAttempt::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $other->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'max_score' => 1,
        ]);

        $this->actingAs($student)->get(route('student.attempts.take', $attempt))->assertForbidden();
        $this->actingAs($student)->get(route('teacher.students.portfolio', $other))->assertForbidden();
    }

    public function test_mass_assignment_ignores_xp_and_level(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $user->update(['xp' => 9999, 'level' => 99, 'name' => 'Safe Name']);
        $user->refresh();
        $this->assertNotSame(9999, (int) $user->xp);
        $this->assertSame('Safe Name', $user->name);
    }

    public function test_cross_teacher_assessment_isolation(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $assessment = Assessment::query()->create([
            'created_by' => $teacherA->id,
            'title' => 'Owned by A',
            'type' => 'quiz',
            'status' => 'draft',
            'pass_score' => 60,
            'max_attempts' => 1,
        ]);

        $this->actingAs($teacherB)->get(route('teacher.assessments.edit', $assessment))->assertForbidden();
    }
}
