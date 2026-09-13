<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionTask;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\XpLedger;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3GamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedBadge(string $slug): Achievement
    {
        return Achievement::query()->create([
            'slug' => $slug,
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'description' => 'Test badge',
            'icon' => 'star',
            'xp_reward' => 0,
            'is_active' => true,
        ]);
    }

    public function test_xp_award_is_idempotent_and_updates_level(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $xp = app(XpService::class);

        $first = $xp->award($student, 'daily_login', 20260913, 'Login');
        $second = $xp->award($student, 'daily_login', 20260913, 'Login again');

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, XpLedger::query()->where('user_id', $student->id)->count());
        $this->assertSame((int) config('xp.events.daily_login'), (int) $student->fresh()->xp);

        $level = $xp->getLevel(150);
        $this->assertSame(2, $level['level']);
        $this->assertSame('Learner', $level['name']);

        $max = $xp->getLevel(10000);
        $this->assertTrue($max['is_max']);
        $this->assertSame(8, $max['level']);
    }

    public function test_teacher_does_not_earn_student_xp(): void
    {
        $teacher = User::factory()->teacher()->create();
        $result = app(XpService::class)->award($teacher, 'daily_login', 1, 'Nope');
        $this->assertNull($result);
        $this->assertSame(0, XpLedger::query()->count());
    }

    public function test_xp_recalculate_command_syncs_totals(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        XpLedger::query()->create([
            'user_id' => $student->id,
            'amount' => 400,
            'source_type' => 'seed_balance',
            'source_id' => 0,
            'description' => 'Seed',
            'awarded_at' => now(),
        ]);
        $student->forceFill(['xp' => 1, 'level' => 1])->save();

        $this->artisan('xp:recalculate')->assertSuccessful();

        $student->refresh();
        $this->assertSame(400, (int) $student->xp);
        $this->assertSame(3, (int) $student->level);
    }

    public function test_badge_award_is_idempotent(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->seedBadge('first-mission');

        MissionEnrollment::query()->create([
            'mission_id' => Mission::query()->create([
                'created_by' => User::factory()->teacher()->create()->id,
                'title' => 'M1',
                'slug' => 'm1-'.uniqid(),
                'status' => 'published',
                'difficulty' => 'beginner',
                'xp_reward' => 50,
            ])->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'progress_percent' => 100,
            'lifecycle_phase' => 'evaluate',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $service = app(\App\Services\BadgeService::class);
        $first = $service->checkAndAward($student);
        $second = $service->checkAndAward($student->fresh());

        $this->assertNotEmpty($first);
        $this->assertSame(1, $student->achievements()->count());
        $this->assertEmpty($second);
    }

    public function test_leaderboard_and_portfolio_gamification_ui(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $student->forceFill(['xp' => 200, 'level' => 2])->save();

        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'LB-1',
            'title' => 'LB',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'LB Room',
            'join_code' => 'LEADER01',
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

        $this->actingAs($student)->get(route('student.leaderboard'))->assertOk()->assertSee('Leaderboard');
        $this->actingAs($student)->get(route('student.profile'))->assertOk()->assertSee('Level progress');
    }

    public function test_assessment_pass_awards_configured_xp(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'XP-A',
            'title' => 'XP',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'XP Room',
            'join_code' => 'XPROOM01',
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

        $service = app(\App\Services\AssessmentService::class);
        $assessment = $service->create($teacher, [
            'title' => 'XP Quiz',
            'type' => 'quiz',
            'classroom_id' => $classroom->id,
            'pass_score' => 50,
            'max_attempts' => 1,
        ]);
        $service->addQuestion($assessment, [
            'type' => 'true_false',
            'prompt' => 'Ok?',
            'options' => ['true', 'false'],
            'correct_answer' => ['true'],
            'points' => 1,
        ]);
        $service->publish($assessment);

        $attempt = $service->startAttempt($student, $assessment);
        $service->autosaveAnswers($attempt, [
            ['question_id' => $assessment->questions()->first()->id, 'selected_option' => 'true'],
        ]);
        $graded = $service->submitAttempt($attempt->fresh());

        $this->assertSame('graded', $graded->status);
        $this->assertTrue(
            XpLedger::query()->where('user_id', $student->id)->where('source_type', 'assessment_passed')->exists()
            || XpLedger::query()->where('user_id', $student->id)->where('source_type', 'assessment_excellent')->exists()
        );
    }
}
