<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\LearningEvidence;
use App\Models\Mission;
use App\Models\MissionTask;
use App\Models\Recommendation;
use App\Models\Skill;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\ClassSessionService;
use App\Services\MissionProgressService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1EndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_phase1_chain_classroom_to_flexlearn(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        // 1. Teacher creates classroom
        $this->actingAs($teacher)
            ->post(route('classrooms.store'), [
                'name' => 'Phase1 Lab',
                'subject' => 'Web Engineering',
                'description' => 'E2E classroom',
                'room_label' => 'Lab A',
            ])
            ->assertRedirect();

        $classroom = Classroom::query()->where('name', 'Phase1 Lab')->firstOrFail();
        $this->assertNotEmpty($classroom->join_code);

        // 2. Student joins
        $this->actingAs($student)
            ->post(route('classrooms.join.store'), ['code' => $classroom->join_code])
            ->assertRedirect(route('classrooms.show', $classroom));

        // 3. Teacher starts session
        $this->actingAs($teacher)
            ->post(route('classrooms.sessions.start', $classroom), [
                'title' => 'E2E Live Session',
            ])
            ->assertRedirect();

        $session = $classroom->liveSession();
        $this->assertNotNull($session);
        $plainCode = app(ClassSessionService::class)->plainCodeFor($session);
        $this->assertNotEmpty($plainCode);

        // QR SVG generates locally
        $svg = app(QrCodeService::class)->svg('PARBATO-ATTEND:'.$plainCode, 120);
        $this->assertStringContainsString('<svg', $svg);

        // 4. Student attends (QR payload prefix accepted)
        $this->actingAs($student)
            ->post(route('classtwin.attendance', $session), [
                'code' => 'PARBATO-ATTEND:'.$plainCode,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', [
            'class_session_id' => $session->id,
            'user_id' => $student->id,
            'status' => 'present',
        ]);

        // 5. Presence heartbeat
        $this->actingAs($student)
            ->postJson(route('classtwin.heartbeat', $session))
            ->assertOk();

        // 6. Teacher ends session → history snapshot
        $this->actingAs($teacher)
            ->post(route('classtwin.end', $session))
            ->assertRedirect(route('classrooms.show', $classroom));

        $session->refresh();
        $this->assertSame('ended', $session->status);
        $this->assertSame(1, $session->present_count_snapshot);

        // 7–10. LearnQuest mission → evaluate → FlexLearn evidence
        $skill = Skill::query()->create(['name' => 'JavaScript', 'slug' => 'javascript-e2e']);
        $mission = Mission::query()->create([
            'created_by' => $teacher->id,
            'course_id' => $classroom->course_id,
            'title' => 'E2E Validator Mission',
            'slug' => 'e2e-validator',
            'description' => 'Test mission',
            'problem_statement' => 'Build it',
            'objective' => 'Ship it',
            'difficulty' => 'beginner',
            'xp_reward' => 100,
            'status' => 'published',
        ]);
        $mission->skills()->attach($skill->id);

        foreach ([
            ['discover', 'Discover'],
            ['learn', 'Learn'],
            ['practice', 'Practice'],
            ['build', 'Build'],
            ['submit', 'Submit'],
            ['present', 'Present'],
            ['evaluate', 'Evaluate'],
        ] as $i => [$phase, $title]) {
            MissionTask::query()->create([
                'mission_id' => $mission->id,
                'title' => $title,
                'description' => $title,
                'phase' => $phase,
                'position' => $i + 1,
                'is_required' => true,
            ]);
        }

        $progress = app(MissionProgressService::class);
        $enrollment = $progress->start($mission, $student);
        foreach ($mission->tasks()->where('phase', '!=', 'evaluate')->orderBy('position')->get() as $task) {
            $enrollment = $progress->completeTask($enrollment, $task, $student);
        }
        $progress->submit($enrollment, $student, [
            'summary' => 'Completed E2E mission submission with enough detail here.',
            'demo_url' => 'https://example.test/demo',
        ]);
        $progress->evaluate($enrollment->fresh(), $teacher, 72, 'Solid work — keep practicing edge cases.');

        $this->assertDatabaseHas('learning_evidences', [
            'user_id' => $student->id,
            'skill_id' => $skill->id,
            'evidence_type' => 'mission_evaluation',
        ]);

        $mastery = StudentSkill::query()
            ->where('user_id', $student->id)
            ->where('skill_id', $skill->id)
            ->first();
        $this->assertNotNull($mastery);
        $this->assertSame(72, (int) $mastery->mastery);

        $this->assertTrue(
            Recommendation::query()
                ->where('user_id', $student->id)
                ->whereIn('status', ['active', 'started'])
                ->exists()
        );

        $this->actingAs($student)
            ->get(route('flexlearn.index'))
            ->assertOk()
            ->assertSee('JavaScript', false)
            ->assertSee('72%', false);

        $this->assertGreaterThan(0, LearningEvidence::query()->where('user_id', $student->id)->count());
    }

    public function test_teacher_can_edit_archive_and_remove_member(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($teacher)->post(route('classrooms.store'), [
            'name' => 'Manage Lab',
            'subject' => 'CS',
        ]);
        $classroom = Classroom::query()->where('name', 'Manage Lab')->firstOrFail();

        $this->actingAs($student)->post(route('classrooms.join.store'), [
            'code' => $classroom->join_code,
        ]);

        $this->actingAs($teacher)
            ->put(route('classrooms.update', $classroom), [
                'name' => 'Manage Lab Updated',
                'subject' => 'Networks',
                'description' => 'Updated desc',
                'room_label' => 'B2',
            ])
            ->assertRedirect(route('classrooms.show', $classroom));

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'name' => 'Manage Lab Updated',
            'subject' => 'Networks',
        ]);

        $this->actingAs($teacher)
            ->post(route('classrooms.members.remove', [$classroom, $student]))
            ->assertRedirect();

        $this->assertDatabaseHas('classroom_members', [
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'status' => 'inactive',
        ]);

        $this->actingAs($teacher)
            ->post(route('classrooms.archive', $classroom))
            ->assertRedirect(route('classrooms.index'));

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'status' => 'archived',
        ]);
    }
}
