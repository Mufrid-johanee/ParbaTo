<?php

namespace Tests\Feature;

use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\LearningEvidence;
use App\Models\PortfolioItem;
use App\Models\Skill;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2IntegrationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase2_assessment_to_portfolio_analytics_notification_chain(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $classroom = Classroom::query()->create([
            'course_id' => Course::query()->create([
                'teacher_id' => $teacher->id,
                'code' => 'P2-1',
                'title' => 'Phase2 Course',
                'status' => 'published',
            ])->id,
            'teacher_id' => $teacher->id,
            'name' => 'Phase2 Lab',
            'join_code' => 'PHASE201',
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

        $skill = Skill::query()->create([
            'name' => 'Forms',
            'slug' => 'forms-e2e',
            'domain' => 'technical',
        ]);

        $service = app(AssessmentService::class);

        $this->actingAs($teacher)
            ->post(route('teacher.assessments.store'), [
                'title' => 'Phase2 Quiz',
                'type' => 'quiz',
                'classroom_id' => $classroom->id,
                'pass_score' => 50,
                'max_attempts' => 2,
            ])
            ->assertRedirect();

        $assessment = \App\Models\Assessment::query()->where('title', 'Phase2 Quiz')->firstOrFail();

        $this->actingAs($teacher)
            ->post(route('teacher.assessments.questions.store', $assessment), [
                'type' => 'mcq',
                'prompt' => 'Required attr?',
                'options' => ['required', 'maybe'],
                'correct_option' => 'required',
                'points' => 2,
                'skill_id' => $skill->id,
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->post(route('teacher.assessments.questions.store', $assessment), [
                'type' => 'true_false',
                'prompt' => 'Labels help a11y',
                'correct_true_false' => 'true',
                'points' => 1,
                'skill_id' => $skill->id,
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->post(route('teacher.assessments.questions.store', $assessment), [
                'type' => 'short_answer',
                'prompt' => 'One rule',
                'points' => 2,
                'skill_id' => $skill->id,
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->post(route('teacher.assessments.publish', $assessment))
            ->assertRedirect();

        $this->assertTrue($student->fresh()->unreadNotifications()->count() >= 1);

        $this->actingAs($student)
            ->post(route('student.assessments.start', $assessment->fresh()))
            ->assertRedirect();

        $attempt = AssessmentAttempt::query()->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->postJson(route('student.attempts.autosave', $attempt), [
                'answers' => [
                    ['question_id' => $assessment->questions()->where('type', 'mcq')->value('id'), 'selected_option' => 'required'],
                    ['question_id' => $assessment->questions()->where('type', 'true_false')->value('id'), 'selected_option' => 'true'],
                    ['question_id' => $assessment->questions()->where('type', 'short_answer')->value('id'), 'answer_text' => 'email'],
                ],
            ])
            ->assertOk();

        // Resume same attempt
        $this->actingAs($student)
            ->post(route('student.assessments.start', $assessment))
            ->assertRedirect(route('student.attempts.take', $attempt));

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt))
            ->assertRedirect();

        $attempt = $attempt->fresh();
        $this->assertSame('submitted', $attempt->status);

        $shortId = $assessment->questions()->where('type', 'short_answer')->value('id');
        $this->actingAs($teacher)
            ->post(route('teacher.assessments.attempts.grade', [$assessment, $attempt]), [
                'grades' => [[
                    'question_id' => $shortId,
                    'points_awarded' => 2,
                    'teacher_feedback' => 'Nice',
                ]],
            ])
            ->assertRedirect();

        $attempt = $attempt->fresh();
        $this->assertSame('graded', $attempt->status);

        $this->assertDatabaseHas('learning_evidences', [
            'user_id' => $student->id,
            'source_type' => AssessmentAttempt::class,
            'source_id' => $attempt->id,
            'evidence_type' => 'assessment',
        ]);

        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $student->id,
            'source_type' => AssessmentAttempt::class,
            'source_id' => $attempt->id,
        ]);

        $this->assertNotNull(StudentSkill::query()->where('user_id', $student->id)->where('skill_id', $skill->id)->first());

        $this->actingAs($student)->get(route('student.profile'))->assertOk()->assertSee('Phase2 Quiz');
        $this->actingAs($teacher)->get(route('analytics.teacher'))->assertOk()->assertSee('Assessments');
        $this->actingAs($student)->get(route('notifications.index'))->assertOk();
    }
}
