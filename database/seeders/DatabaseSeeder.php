<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseModule;
use App\Models\Material;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionResource;
use App\Models\MissionTask;
use App\Models\Question;
use App\Models\Skill;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\FlexLearnRecommendationService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->create([
            'name' => 'ParbaTo Admin',
            'display_name' => 'Admin',
            'email' => 'admin@parbato.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'xp' => 0,
            'level' => 1,
            'email_verified_at' => now(),
        ]);

        $teacher = User::query()->create([
            'name' => 'David Vance',
            'display_name' => 'Prof. Vance',
            'email' => 'teacher@parbato.test',
            'password' => 'password',
            'role' => User::ROLE_TEACHER,
            'xp' => 0,
            'level' => 1,
            'major' => 'Computer Science',
            'email_verified_at' => now(),
        ]);

        $alex = User::query()->create([
            'name' => 'Alex Rahman',
            'display_name' => 'Alex',
            'email' => 'student@parbato.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'xp' => 3450,
            'level' => 14,
            'major' => 'Computer Science',
            'email_verified_at' => now(),
        ]);

        $students = collect([$alex]);
        foreach ([
            ['Maya Reyes', 'Maya', 'maya@parbato.test'],
            ['Tariq Nasser', 'Tariq', 'tariq@parbato.test'],
            ['Sara Ahmed', 'Sara', 'sara@parbato.test'],
            ['Liam Kelly', 'Liam', 'liam@parbato.test'],
        ] as [$name, $display, $email]) {
            $students->push(User::query()->create([
                'name' => $name,
                'display_name' => $display,
                'email' => $email,
                'password' => 'password',
                'role' => User::ROLE_STUDENT,
                'xp' => random_int(200, 2000),
                'level' => random_int(3, 12),
                'major' => 'Computer Science',
                'email_verified_at' => now(),
            ]));
        }

        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'code' => 'CS-201',
            'title' => 'Web Programming',
            'description' => 'HTML forms, validation, and client-server interaction.',
            'term' => 'Fall 2026',
            'status' => 'published',
        ]);

        $module = CourseModule::query()->create([
            'course_id' => $course->id,
            'title' => 'Forms & Validation',
            'description' => 'Client and server validation patterns.',
            'position' => 1,
        ]);

        Material::query()->create([
            'course_module_id' => $module->id,
            'title' => 'HTML Form Semantics',
            'type' => 'link',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn/Forms',
            'position' => 1,
        ]);

        foreach ($students as $student) {
            CourseEnrollment::query()->create([
                'course_id' => $course->id,
                'user_id' => $student->id,
                'status' => 'active',
                'enrolled_at' => now()->subDays(14),
            ]);
        }

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'CS-201 Lab Twin',
            'room_label' => 'Turing Hall 302',
            'capacity' => 30,
            'rows' => 5,
            'cols' => 6,
        ]);

        $desk = 1;
        foreach ($students as $index => $student) {
            $row = intdiv($index, 6) + 1;
            $col = ($index % 6) + 1;
            ClassroomMember::query()->create([
                'classroom_id' => $classroom->id,
                'user_id' => $student->id,
                'desk_row' => $row,
                'desk_col' => $col,
                'desk_label' => 'D-'.str_pad((string) $desk, 2, '0', STR_PAD_LEFT),
            ]);
            $desk++;
        }

        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'started_by' => $teacher->id,
            'title' => 'Web Programming — HTML Forms',
            'status' => 'live',
            'started_at' => now()->subMinutes(42),
            'attendance_code' => hash('sha256', 'PARBATO1'),
            'attendance_code_expires_at' => now()->addHours(4),
            'notes' => 'Demo live ClassTwin session',
        ]);

        AttendanceRecord::query()->create([
            'class_session_id' => $session->id,
            'user_id' => $alex->id,
            'method' => 'qr',
            'status' => 'present',
            'checked_in_at' => now()->subMinutes(40),
        ]);

        $skills = collect([
            ['form-validation', 'Form Validation', 'web'],
            ['html-semantics', 'HTML Semantics', 'web'],
            ['async-api', 'Async API', 'web'],
            ['accessibility', 'Accessibility', 'web'],
        ])->map(fn ($s) => Skill::query()->create([
            'slug' => $s[0],
            'name' => $s[1],
            'description' => $s[1].' competency',
            'domain' => $s[2],
        ]));

        $masteryMap = [
            'student@parbato.test' => [48, 72, 65, 40],
            'maya@parbato.test' => [70, 80, 55, 60],
            'tariq@parbato.test' => [82, 75, 88, 70],
            'sara@parbato.test' => [45, 50, 42, 55],
            'liam@parbato.test' => [38, 60, 48, 35],
        ];

        foreach ($students as $student) {
            $values = $masteryMap[$student->email] ?? [60, 60, 60, 60];
            foreach ($skills as $i => $skill) {
                StudentSkill::query()->create([
                    'user_id' => $student->id,
                    'skill_id' => $skill->id,
                    'mastery' => $values[$i],
                    'evidence_count' => random_int(1, 5),
                    'last_assessed_at' => now()->subDays(random_int(1, 7)),
                ]);
            }
        }

        $mission = Mission::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'Build Registration Form Validator',
            'slug' => 'build-registration-form-validator',
            'description' => 'Design and implement a resilient registration form with client and server validation.',
            'problem_statement' => "Campus clubs keep receiving incomplete event registrations.\nBuild a validator that catches invalid emails, weak passwords, and missing required fields before submission.",
            'objective' => 'Ship a working validator with clear error states and at least one automated check.',
            'difficulty' => 'intermediate',
            'estimated_minutes' => 180,
            'xp_reward' => 250,
            'mode' => 'individual',
            'status' => 'published',
            'deadline_at' => now()->addDays(10),
            'assessment_criteria' => ['Validation coverage', 'UX clarity', 'Accessibility'],
            'submission_requirements' => ['Repo URL or uploaded zip', 'Short demo notes'],
        ]);

        $mission->skills()->attach($skills->take(2)->pluck('id'));

        foreach ([
            ['discover', 'Review current broken form flows'],
            ['learn', 'Study HTML constraint validation APIs'],
            ['practice', 'Implement email and password rules'],
            ['build', 'Assemble the full registration validator'],
            ['submit', 'Submit demo + reflection'],
        ] as $i => [$phase, $title]) {
            MissionTask::query()->create([
                'mission_id' => $mission->id,
                'title' => $title,
                'description' => $title,
                'phase' => $phase,
                'position' => $i + 1,
            ]);
        }

        MissionResource::query()->create([
            'mission_id' => $mission->id,
            'title' => 'MDN Constraint Validation',
            'type' => 'link',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Constraint_validation',
            'position' => 1,
        ]);

        Mission::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'Campus Navigation Micro-App',
            'slug' => 'campus-navigation-micro-app',
            'description' => 'Prototype a lightweight campus wayfinding experience.',
            'problem_statement' => 'New students struggle to find labs and lecture halls on day one.',
            'objective' => 'Deliver a clickable prototype with three routes and clear landmarks.',
            'difficulty' => 'beginner',
            'estimated_minutes' => 120,
            'xp_reward' => 150,
            'mode' => 'team',
            'status' => 'published',
            'deadline_at' => now()->addDays(14),
        ])->skills()->attach($skills->pluck('id')->slice(2, 2));

        MissionEnrollment::query()->create([
            'mission_id' => $mission->id,
            'user_id' => $alex->id,
            'status' => 'in_progress',
            'lifecycle_phase' => 'practice',
            'progress_percent' => 45,
            'started_at' => now()->subDays(3),
        ]);

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'Form Validation Pre-check',
            'type' => 'pre_assessment',
            'instructions' => 'Quick check on validation concepts.',
            'pass_score' => 60,
            'status' => 'published',
        ]);

        Question::query()->create([
            'assessment_id' => $assessment->id,
            'skill_id' => $skills[0]->id,
            'type' => 'mcq',
            'prompt' => 'Which attribute marks an input as required?',
            'options' => ['required', 'validate', 'must', 'needed'],
            'correct_answer' => ['required'],
            'points' => 1,
            'position' => 1,
        ]);

        AssessmentAttempt::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $alex->id,
            'score' => 1,
            'max_score' => 2,
            'accuracy' => 50,
            'status' => 'graded',
            'answers' => ['required'],
            'started_at' => now()->subDays(5),
            'submitted_at' => now()->subDays(5),
        ]);

        app(FlexLearnRecommendationService::class)->generateFor($alex);

        unset($admin); // silence unused in some linters; retained for demo login

        $this->command?->info('Demo accounts (password: password):');
        $this->command?->info('  student@parbato.test');
        $this->command?->info('  teacher@parbato.test');
        $this->command?->info('  admin@parbato.test');
        $this->command?->info('Live attendance code: PARBATO1');
    }
}
