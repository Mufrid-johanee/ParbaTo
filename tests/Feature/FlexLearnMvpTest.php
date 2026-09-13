<?php

namespace Tests\Feature;

use App\Models\LearningEvidence;
use App\Models\Mission;
use App\Models\MissionTask;
use App\Models\Recommendation;
use App\Models\Skill;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\FlexLearnRecommendationService;
use App\Services\MasteryService;
use App\Services\MissionProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlexLearnMvpTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected Mission $mission;

    protected Skill $skillJs;

    protected Skill $skillValidation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->skillJs = Skill::query()->create([
            'name' => 'JavaScript',
            'slug' => 'javascript',
        ]);
        $this->skillValidation = Skill::query()->create([
            'name' => 'Form Validation',
            'slug' => 'form-validation',
        ]);

        $this->mission = Mission::query()->create([
            'created_by' => $this->teacher->id,
            'title' => 'Build Registration Form Validator',
            'slug' => 'form-validator-flexlearn',
            'description' => 'Validate forms.',
            'problem_statement' => 'Build a validator.',
            'objective' => 'Ship validation.',
            'difficulty' => 'intermediate',
            'xp_reward' => 200,
            'status' => 'published',
        ]);

        $this->mission->skills()->attach([
            $this->skillJs->id,
            $this->skillValidation->id,
        ]);

        $defs = [
            ['discover', 'Understand problem'],
            ['learn', 'Learn validation'],
            ['practice', 'Practice rules'],
            ['build', 'Build validator'],
            ['submit', 'Submit project'],
            ['present', 'Prepare demo'],
            ['evaluate', 'Teacher evaluates'],
        ];

        foreach ($defs as $i => [$phase, $title]) {
            MissionTask::query()->create([
                'mission_id' => $this->mission->id,
                'title' => $title,
                'description' => $title,
                'phase' => $phase,
                'position' => $i + 1,
                'is_required' => true,
            ]);
        }
    }

    public function test_mastery_is_deterministic_from_evidence(): void
    {
        $mastery = app(MasteryService::class);

        $mastery->record($this->student, $this->skillJs->id, Mission::class, 1, 'mission_evaluation', 58, 70);
        $first = StudentSkill::query()->where('user_id', $this->student->id)->where('skill_id', $this->skillJs->id)->first();

        $mastery->recalculateSkill($this->student, $this->skillJs->id);
        $second = StudentSkill::query()->where('user_id', $this->student->id)->where('skill_id', $this->skillJs->id)->first();

        $this->assertSame(58, (int) $first->mastery);
        $this->assertSame((int) $first->mastery, (int) $second->mastery);
        $this->assertSame('Developing', MasteryService::bandLabel(58));
    }

    public function test_evaluate_creates_evidence_updates_mastery_and_recommendations(): void
    {
        $service = app(MissionProgressService::class);
        $enrollment = $service->start($this->mission, $this->student);

        foreach ($this->mission->tasks()->where('phase', '!=', 'evaluate')->orderBy('position')->get() as $task) {
            $enrollment = $service->completeTask($enrollment, $task, $this->student);
        }

        $service->submit($enrollment, $this->student, [
            'summary' => 'Validator demo with enough detail for a valid submission summary.',
            'demo_url' => 'https://example.test/validator',
        ]);

        $enrollment = $enrollment->fresh();
        $service->evaluate($enrollment, $this->teacher, 58, 'Needs more edge-case coverage.');

        $this->assertDatabaseHas('learning_evidences', [
            'user_id' => $this->student->id,
            'skill_id' => $this->skillValidation->id,
            'evidence_type' => 'mission_evaluation',
            'score' => 58,
        ]);

        $this->assertDatabaseHas('student_skills', [
            'user_id' => $this->student->id,
            'skill_id' => $this->skillValidation->id,
            'mastery' => 58,
        ]);

        $this->assertDatabaseHas('recommendations', [
            'user_id' => $this->student->id,
            'skill_id' => $this->skillValidation->id,
            'status' => 'active',
        ]);

        $rec = Recommendation::query()
            ->where('user_id', $this->student->id)
            ->where('skill_id', $this->skillValidation->id)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($rec);
        $this->assertStringContainsString('Developing', $rec->reason);
        $this->assertStringContainsString('58%', $rec->reason);
    }

    public function test_student_cannot_view_another_students_recommendation_actions(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $rec = Recommendation::query()->create([
            'user_id' => $this->student->id,
            'skill_id' => $this->skillJs->id,
            'title' => 'Practice JS',
            'reason' => 'Because mastery is low.',
            'priority' => 'high',
            'sort_order' => 10,
            'status' => 'active',
            'rule_key' => 'needs_support:'.$this->skillJs->id,
        ]);

        $this->actingAs($other)
            ->post(route('flexlearn.recommendations.start', $rec))
            ->assertForbidden();

        $this->actingAs($other)
            ->postJson('/api/flexlearn/recommendations/'.$rec->id.'/start')
            ->assertForbidden();
    }

    public function test_student_can_start_own_recommendation(): void
    {
        $rec = Recommendation::query()->create([
            'user_id' => $this->student->id,
            'mission_id' => $this->mission->id,
            'title' => 'Continue mission',
            'reason' => 'You have unfinished work.',
            'action_label' => 'Resume',
            'action_url' => route('learnquest.show', $this->mission),
            'priority' => 'high',
            'sort_order' => 5,
            'status' => 'active',
            'rule_key' => 'resume_mission:'.$this->mission->id,
        ]);

        $this->actingAs($this->student)
            ->post(route('flexlearn.recommendations.start', $rec))
            ->assertRedirect(route('learnquest.show', $this->mission));

        $this->assertSame('started', $rec->fresh()->status);
    }

    public function test_flexlearn_page_shows_empty_state_without_fake_mastery(): void
    {
        $this->actingAs($this->student)
            ->get(route('flexlearn.index'))
            ->assertOk()
            ->assertSee('Complete your first mission to unlock your personalized learning path', false)
            ->assertDontSee('82%', false);
    }

    public function test_teacher_can_view_student_mastery(): void
    {
        app(MasteryService::class)->record(
            $this->student,
            $this->skillJs->id,
            Mission::class,
            9,
            'mission_evaluation',
            82,
            70
        );
        app(FlexLearnRecommendationService::class)->refreshFor($this->student);

        $this->actingAs($this->teacher)
            ->get(route('flexlearn.teacher.show', $this->student))
            ->assertOk()
            ->assertSee('JavaScript', false)
            ->assertSee('82%', false);

        $intruder = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->actingAs($intruder)
            ->get(route('flexlearn.teacher.show', $this->student))
            ->assertForbidden();
    }

    public function test_weighted_mastery_average_is_explainable(): void
    {
        $mastery = app(MasteryService::class);
        $mastery->record($this->student, $this->skillJs->id, 'A', 1, 'mission_evaluation', 40, 50);
        $mastery->record($this->student, $this->skillJs->id, 'B', 2, 'mission_evaluation', 80, 50);

        $skill = StudentSkill::query()
            ->where('user_id', $this->student->id)
            ->where('skill_id', $this->skillJs->id)
            ->first();

        // (40*50 + 80*50) / 100 = 60
        $this->assertSame(60, (int) $skill->mastery);
        $this->assertSame(2, LearningEvidence::query()->where('user_id', $this->student->id)->count());
    }
}
