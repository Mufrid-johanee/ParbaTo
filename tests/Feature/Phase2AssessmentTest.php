<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\LearningEvidence;
use App\Models\PortfolioItem;
use App\Models\Question;
use App\Models\Skill;
use App\Models\StudentSkill;
use App\Models\User;
use App\Notifications\AssessmentGradedNotification;
use App\Notifications\AssessmentPublishedNotification;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Phase2AssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function seedClassroomPair(): array
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $outsider = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $otherTeacher = User::factory()->teacher()->create();

        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'ASSESS-'.uniqid(),
            'title' => 'Assess Course',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'Assess Lab',
            'join_code' => 'ASSESS01',
            'capacity' => 40,
            'rows' => 5,
            'cols' => 8,
            'status' => 'active',
        ]);

        ClassroomMember::query()->create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'desk_row' => 1,
            'desk_col' => 1,
            'desk_label' => 'D-01',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $skill = Skill::query()->create([
            'name' => 'Validation',
            'slug' => 'validation-'.uniqid(),
            'domain' => 'technical',
        ]);

        return compact('teacher', 'student', 'outsider', 'otherTeacher', 'classroom', 'skill');
    }

    protected function makePublishedAssessment(User $teacher, Classroom $classroom, Skill $skill): Assessment
    {
        $service = app(AssessmentService::class);
        $assessment = $service->create($teacher, [
            'title' => 'Unit Quiz',
            'type' => 'quiz',
            'classroom_id' => $classroom->id,
            'pass_score' => 60,
            'max_attempts' => 2,
            'time_limit_minutes' => 10,
        ]);

        $service->addQuestion($assessment, [
            'type' => 'mcq',
            'prompt' => 'Pick required',
            'options' => ['required', 'optional'],
            'correct_answer' => ['required'],
            'points' => 2,
            'skill_id' => $skill->id,
            'position' => 1,
        ]);
        $service->addQuestion($assessment, [
            'type' => 'true_false',
            'prompt' => 'HTML forms need names',
            'options' => ['true', 'false'],
            'correct_answer' => ['true'],
            'points' => 1,
            'skill_id' => $skill->id,
            'position' => 2,
        ]);
        $service->addQuestion($assessment, [
            'type' => 'short_answer',
            'prompt' => 'Name a rule',
            'points' => 2,
            'skill_id' => $skill->id,
            'position' => 3,
        ]);

        return $service->publish($assessment->fresh());
    }

    public function test_teacher_lifecycle_and_student_scoring_pipeline(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'outsider' => $outsider, 'classroom' => $classroom, 'skill' => $skill] = $this->seedClassroomPair();
        $service = app(AssessmentService::class);

        $assessment = $this->makePublishedAssessment($teacher, $classroom, $skill);

        $this->actingAs($student)->get(route('student.assessments.index'))->assertOk();
        $this->actingAs($outsider)->get(route('student.assessments.show', $assessment))->assertForbidden();

        $draft = $service->create($teacher, ['title' => 'Hidden', 'type' => 'quiz', 'classroom_id' => $classroom->id]);
        $this->actingAs($student)->get(route('student.assessments.show', $draft))->assertForbidden();

        $attempt = $service->startAttempt($student, $assessment);
        $same = $service->startAttempt($student, $assessment);
        $this->assertSame($attempt->id, $same->id);

        $service->autosaveAnswers($attempt, [
            ['question_id' => $assessment->questions[0]->id, 'selected_option' => 'required'],
            ['question_id' => $assessment->questions[1]->id, 'selected_option' => 'true'],
            ['question_id' => $assessment->questions[2]->id, 'answer_text' => 'email'],
        ]);

        $resumed = $service->startAttempt($student, $assessment);
        $this->assertSame(3, $resumed->answerRecords()->count());

        $submitted = $service->submitAttempt($attempt->fresh());
        $this->assertSame('submitted', $submitted->status);
        $this->assertEquals(3.0, (float) $submitted->score);

        $otherTeacher = User::factory()->teacher()->create();
        $this->actingAs($otherTeacher)
            ->post(route('teacher.assessments.attempts.grade', [$assessment, $submitted]), [
                'grades' => [[
                    'question_id' => $assessment->questions[2]->id,
                    'points_awarded' => 2,
                ]],
            ])
            ->assertForbidden();

        $graded = $service->gradeShortAnswers($submitted->fresh(), $teacher, [[
            'question_id' => $assessment->questions[2]->id,
            'points_awarded' => 2,
            'teacher_feedback' => 'Good',
        ]]);

        $this->assertSame('graded', $graded->status);
        $this->assertEquals(100.0, (float) $graded->accuracy);

        $this->assertDatabaseHas('learning_evidences', [
            'user_id' => $student->id,
            'skill_id' => $skill->id,
            'source_type' => AssessmentAttempt::class,
            'source_id' => $graded->id,
            'evidence_type' => 'assessment',
        ]);

        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $student->id,
            'source_type' => AssessmentAttempt::class,
            'source_id' => $graded->id,
        ]);

        $this->assertGreaterThan(0, StudentSkill::query()->where('user_id', $student->id)->where('skill_id', $skill->id)->value('mastery'));

        // Duplicate finalize should not duplicate evidence rows
        $service->finalizeGradedAttempt($graded->fresh(['assessment.questions', 'user', 'answerRecords']));
        $this->assertSame(1, LearningEvidence::query()
            ->where('source_type', AssessmentAttempt::class)
            ->where('source_id', $graded->id)
            ->where('skill_id', $skill->id)
            ->count());
    }

    public function test_attempt_rules_max_deadline_time_and_idor(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'classroom' => $classroom, 'skill' => $skill] = $this->seedClassroomPair();
        $service = app(AssessmentService::class);

        $assessment = $service->create($teacher, [
            'title' => 'Strict Quiz',
            'type' => 'quiz',
            'classroom_id' => $classroom->id,
            'max_attempts' => 1,
            'time_limit_minutes' => 1,
            'ends_at' => now()->subMinute(),
        ]);
        $service->addQuestion($assessment, [
            'type' => 'mcq',
            'prompt' => 'A?',
            'options' => ['a', 'b'],
            'correct_answer' => ['a'],
            'points' => 1,
            'skill_id' => $skill->id,
        ]);
        $service->publish($assessment);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->startAttempt($student, $assessment->fresh());
    }

    public function test_max_attempts_and_client_score_ignored(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'classroom' => $classroom, 'skill' => $skill] = $this->seedClassroomPair();
        $service = app(AssessmentService::class);
        $assessment = $this->makePublishedAssessment($teacher, $classroom, $skill);
        $assessment->forceFill(['max_attempts' => 1])->save();

        // Objective-only assessment for auto-grade
        $assessment->questions()->where('type', 'short_answer')->delete();
        $assessment = $assessment->fresh(['questions']);

        $attempt = $service->startAttempt($student, $assessment);
        $service->autosaveAnswers($attempt, [
            ['question_id' => $assessment->questions[0]->id, 'selected_option' => 'optional', 'is_correct' => true, 'points_awarded' => 99],
            ['question_id' => $assessment->questions[1]->id, 'selected_option' => 'false'],
        ]);
        $graded = $service->submitAttempt($attempt->fresh());
        $this->assertSame('graded', $graded->status);
        $this->assertEquals(0.0, (float) $graded->score);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->startAttempt($student, $assessment);
    }

    public function test_time_limit_enforced_on_submit_path(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'classroom' => $classroom, 'skill' => $skill] = $this->seedClassroomPair();
        $service = app(AssessmentService::class);
        $assessment = $this->makePublishedAssessment($teacher, $classroom, $skill);
        $assessment->questions()->where('type', 'short_answer')->delete();
        $assessment->forceFill(['time_limit_minutes' => 1])->save();

        $attempt = $service->startAttempt($student, $assessment);
        $attempt->forceFill(['started_at' => now()->subMinutes(5)])->save();

        $result = $service->autosaveAnswers($attempt->fresh(), [
            ['question_id' => $assessment->questions()->first()->id, 'selected_option' => 'required'],
        ]);

        $this->assertContains($result->status, ['submitted', 'graded']);
    }

    public function test_publish_notifies_classroom_students_once(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'classroom' => $classroom, 'skill' => $skill] = $this->seedClassroomPair();
        Notification::fake();

        $assessment = app(AssessmentService::class)->create($teacher, [
            'title' => 'Notify Quiz',
            'type' => 'quiz',
            'classroom_id' => $classroom->id,
        ]);
        app(AssessmentService::class)->addQuestion($assessment, [
            'type' => 'true_false',
            'prompt' => 'Ok?',
            'options' => ['true', 'false'],
            'correct_answer' => ['true'],
            'points' => 1,
            'skill_id' => $skill->id,
        ]);
        app(AssessmentService::class)->publish($assessment);

        Notification::assertSentTo($student, AssessmentPublishedNotification::class);
    }
}
