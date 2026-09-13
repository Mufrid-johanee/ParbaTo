<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\ClassSessionService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3EndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_teacher_phase3_journeys_award_xp_and_notifications(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($teacher)
            ->post(route('classrooms.store'), [
                'name' => 'Phase3 Lab',
                'subject' => 'Web',
                'description' => 'P3',
                'room_label' => 'Lab 3',
            ])
            ->assertRedirect();

        $classroom = Classroom::query()->where('name', 'Phase3 Lab')->firstOrFail();

        $this->actingAs($student)
            ->post(route('classrooms.join.store'), ['code' => $classroom->join_code])
            ->assertRedirect();

        $this->assertDatabaseHas('xp_ledger', [
            'user_id' => $student->id,
            'source_type' => 'classroom_joined',
            'source_id' => $classroom->id,
        ]);

        $this->actingAs($teacher)
            ->post(route('classrooms.sessions.start', $classroom), ['title' => 'P3 Live'])
            ->assertRedirect();

        $session = $classroom->liveSession();
        $plain = app(ClassSessionService::class)->plainCodeFor($session);

        $this->actingAs($student)
            ->post(route('classtwin.attendance', $session), ['code' => $plain])
            ->assertRedirect();

        $this->assertDatabaseHas('xp_ledger', [
            'user_id' => $student->id,
            'source_type' => 'classtwin_attendance',
        ]);

        $service = app(AssessmentService::class);
        $assessment = $service->create($teacher, [
            'title' => 'P3 Quiz',
            'type' => 'quiz',
            'classroom_id' => $classroom->id,
            'pass_score' => 50,
            'max_attempts' => 1,
        ]);
        $service->addQuestion($assessment, [
            'type' => 'true_false',
            'prompt' => 'Ready?',
            'options' => ['true', 'false'],
            'correct_answer' => ['true'],
            'points' => 1,
        ]);
        $service->publish($assessment->fresh());

        $attempt = $service->startAttempt($student->fresh(), $assessment->fresh());
        $service->autosaveAnswers($attempt, [
            ['question_id' => $assessment->questions()->first()->id, 'selected_option' => 'true'],
        ]);
        $service->submitAttempt($attempt->fresh());

        $this->actingAs($student)->get(route('student.profile'))->assertOk();
        $this->actingAs($teacher)->get(route('analytics.teacher'))->assertOk();
        $this->actingAs($student)->get(route('notifications.index'))->assertOk();

        $level = app(XpService::class)->getLevel((int) $student->fresh()->xp);
        $this->assertGreaterThanOrEqual(1, $level['level']);
    }
}
