<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionTask;
use App\Models\User;
use App\Services\MissionProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnQuestMissionWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected Mission $mission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->mission = Mission::query()->create([
            'created_by' => $this->teacher->id,
            'title' => 'Campus Navigation Micro-App',
            'slug' => 'campus-nav-test',
            'description' => 'Test mission',
            'problem_statement' => 'Find buildings.',
            'objective' => 'Build a prototype.',
            'difficulty' => 'beginner',
            'xp_reward' => 150,
            'status' => 'published',
        ]);

        $defs = [
            ['discover', 'Understand problem'],
            ['learn', 'Learn APIs'],
            ['practice', 'Practice location'],
            ['build', 'Build UI'],
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

    public function test_start_mission_creates_enrollment_once(): void
    {
        $this->actingAs($this->student)
            ->post(route('learnquest.start', $this->mission))
            ->assertRedirect(route('learnquest.show', $this->mission));

        $this->assertDatabaseCount('mission_enrollments', 1);
        $this->assertDatabaseHas('mission_enrollments', [
            'mission_id' => $this->mission->id,
            'user_id' => $this->student->id,
            'status' => 'in_progress',
            'lifecycle_phase' => 'discover',
            'progress_percent' => 0,
        ]);

        $this->actingAs($this->student)
            ->post(route('learnquest.start', $this->mission))
            ->assertRedirect();

        $this->assertDatabaseCount('mission_enrollments', 1);
    }

    public function test_completing_tasks_updates_progress_and_phase(): void
    {
        $service = app(MissionProgressService::class);
        $enrollment = $service->start($this->mission, $this->student);

        $discover = $this->mission->tasks()->where('phase', 'discover')->first();
        $enrollment = $service->completeTask($enrollment, $discover, $this->student);

        $this->assertSame((int) round((1 / 7) * 100), $enrollment->progress_percent);
        $this->assertSame('learn', $enrollment->lifecycle_phase);

        $this->actingAs($this->student)
            ->post(route('learnquest.tasks.complete', [$this->mission, $discover]))
            ->assertRedirect();

        $this->assertDatabaseCount('mission_task_progress', 1);
    }

    public function test_student_cannot_complete_another_students_task(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $service = app(MissionProgressService::class);
        $service->start($this->mission, $this->student);
        $task = $this->mission->tasks()->where('phase', 'discover')->first();

        $this->actingAs($other)
            ->post(route('learnquest.tasks.complete', [$this->mission, $task]))
            ->assertNotFound();
    }

    public function test_full_flow_submit_and_teacher_evaluate(): void
    {
        $service = app(MissionProgressService::class);
        $enrollment = $service->start($this->mission, $this->student);

        foreach ($this->mission->tasks()->where('phase', '!=', 'evaluate')->orderBy('position')->get() as $task) {
            $enrollment = $service->completeTask($enrollment, $task, $this->student);
        }

        $this->assertSame('evaluate', $enrollment->lifecycle_phase);
        $this->assertSame((int) round((6 / 7) * 100), $enrollment->progress_percent);

        $this->actingAs($this->student)
            ->post(route('learnquest.submit', $this->mission), [
                'summary' => 'Built a campus navigation prototype with three routes and accessible labels.',
                'demo_url' => 'https://example.com/demo',
            ])
            ->assertRedirect();

        $enrollment->refresh();
        $this->assertSame('submitted', $enrollment->status);

        $this->actingAs($this->teacher)
            ->post(route('learnquest.evaluations.store', $enrollment), [
                'score' => 88,
                'feedback' => 'Strong prototype. Improve landmark contrast next time.',
            ])
            ->assertRedirect(route('learnquest.evaluations.index'));

        $enrollment->refresh();
        $this->assertSame('completed', $enrollment->status);
        $this->assertSame(100, $enrollment->progress_percent);
        $this->assertDatabaseHas('mission_submissions', [
            'mission_enrollment_id' => $enrollment->id,
            'status' => 'accepted',
            'score' => 88,
        ]);
        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $this->student->id,
            'title' => $this->mission->title,
        ]);
    }

    public function test_student_cannot_evaluate(): void
    {
        $service = app(MissionProgressService::class);
        $enrollment = $service->start($this->mission, $this->student);
        foreach ($this->mission->tasks()->where('phase', '!=', 'evaluate')->get() as $task) {
            $enrollment = $service->completeTask($enrollment, $task, $this->student);
        }
        $service->submit($enrollment, $this->student, [
            'summary' => 'Enough characters for a valid mission project summary here.',
        ]);

        $this->actingAs($this->student)
            ->post(route('learnquest.evaluations.store', $enrollment), [
                'score' => 100,
                'feedback' => 'I am evaluating myself which should fail.',
            ])
            ->assertForbidden();
    }
}
