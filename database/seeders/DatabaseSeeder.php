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

        // 10 uniquely named demo students (shared password: password)
        $demoStudents = [
            ['Alex Rahman', 'Alex', 'student@parbato.test', 3450, 14, [48, 72, 65, 40]],
            ['Maya Reyes', 'Maya', 'maya@parbato.test', 1800, 9, [70, 80, 55, 60]],
            ['Tariq Nasser', 'Tariq', 'tariq@parbato.test', 2200, 11, [82, 75, 88, 70]],
            ['Sara Ahmed', 'Sara', 'sara@parbato.test', 900, 5, [45, 50, 42, 55]],
            ['Liam Kelly', 'Liam', 'liam@parbato.test', 650, 4, [38, 60, 48, 35]],
            ['Nadia Chowdhury', 'Nadia', 'nadia@parbato.test', 1400, 7, [62, 58, 71, 49]],
            ['Omar Hassan', 'Omar', 'omar@parbato.test', 1100, 6, [55, 67, 44, 72]],
            ['Priya Sen', 'Priya', 'priya@parbato.test', 1950, 10, [78, 81, 69, 74]],
            ['Ethan Brooks', 'Ethan', 'ethan@parbato.test', 780, 4, [41, 53, 60, 47]],
            ['Aisha Karim', 'Aisha', 'aisha@parbato.test', 1600, 8, [66, 59, 73, 51]],
        ];

        $students = collect();
        $masteryMap = [];
        foreach ($demoStudents as [$name, $display, $email, $xp, $level, $mastery]) {
            $students->push(User::query()->create([
                'name' => $name,
                'display_name' => $display,
                'email' => $email,
                'password' => 'password',
                'role' => User::ROLE_STUDENT,
                'xp' => $xp,
                'level' => $level,
                'major' => 'Computer Science',
                'email_verified_at' => now(),
            ]));
            $masteryMap[$email] = $mastery;
        }

        $alex = $students->firstWhere('email', 'student@parbato.test');

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
            'subject' => 'Web Programming',
            'description' => 'Digital twin for the CS-201 lab sessions.',
            'join_code' => 'JOIN201A',
            'room_label' => 'Turing Hall 302',
            'capacity' => 30,
            'rows' => 5,
            'cols' => 6,
            'status' => 'active',
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
                'status' => 'active',
                'joined_at' => now()->subDays(10),
            ]);
            $desk++;
        }

        // Ended session for history demo
        ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'started_by' => $teacher->id,
            'title' => 'Orientation — Lab Safety',
            'status' => 'ended',
            'started_at' => now()->subDays(3)->setTime(10, 0),
            'ended_at' => now()->subDays(3)->setTime(11, 15),
            'member_count_snapshot' => 10,
            'present_count_snapshot' => 8,
            'notes' => 'Demo ended session',
        ]);

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

        \Illuminate\Support\Facades\Cache::put(
            'classtwin.session.'.$session->id.'.attendance_code',
            'PARBATO1',
            now()->addHours(4)
        );

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
            ['discover', 'Review current broken form flows', 'Map where registration data fails today.'],
            ['discover', 'Identify required registration fields', 'List must-have fields and validation rules.'],
            ['learn', 'Study HTML constraint validation APIs', 'Read constraint validation concepts and attributes.'],
            ['practice', 'Implement email and password rules', 'Code client-side checks for email and password strength.'],
            ['build', 'Assemble the full registration validator', 'Combine fields, errors, and submit gating into one form.'],
            ['submit', 'Submit demo + reflection', 'Prepare a short write-up of what you built.'],
            ['present', 'Prepare a short demo walkthrough', 'Outline how you will show the validator working.'],
            ['evaluate', 'Teacher evaluates the project', 'Faculty scores the submission and records feedback.'],
        ] as $i => [$phase, $title, $description]) {
            MissionTask::query()->create([
                'mission_id' => $mission->id,
                'title' => $title,
                'description' => $description,
                'phase' => $phase,
                'position' => $i + 1,
                'is_required' => true,
            ]);
        }

        MissionResource::query()->create([
            'mission_id' => $mission->id,
            'title' => 'MDN Constraint Validation',
            'type' => 'link',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Constraint_validation',
            'position' => 1,
        ]);

        MissionResource::query()->create([
            'mission_id' => $mission->id,
            'title' => 'MDN HTML Forms Guide',
            'type' => 'article',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Extensions/Forms',
            'position' => 2,
        ]);

        $campus = Mission::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'Campus Navigation Micro-App',
            'slug' => 'campus-navigation-micro-app',
            'description' => 'Prototype a lightweight campus wayfinding experience.',
            'problem_statement' => "New students struggle to find labs and lecture halls on day one.\nBuild a simple navigation micro-app that helps them reach three key campus destinations.",
            'objective' => 'Deliver a clickable prototype with three routes, clear landmarks, and accessible UI cues.',
            'difficulty' => 'beginner',
            'estimated_minutes' => 120,
            'xp_reward' => 150,
            'mode' => 'team',
            'status' => 'published',
            'deadline_at' => now()->addDays(14),
            'assessment_criteria' => ['Us clarity', 'Async API usage', 'Accessibility'],
            'submission_requirements' => ['Project explanation', 'Optional demo URL'],
        ]);
        $campus->skills()->attach($skills->pluck('id')->slice(2, 2));

        foreach ([
            ['discover', 'Understand the campus navigation problem', 'Write the pain points for first-day students.'],
            ['discover', 'Identify the target users', 'Define who the micro-app helps and their constraints.'],
            ['discover', 'Define the core navigation requirements', 'List destinations, landmarks, and success criteria.'],
            ['learn', 'Learn how location/navigation APIs work', 'Study browser geolocation / async location patterns.'],
            ['learn', 'Learn basic accessibility principles', 'Review accessible labels, contrast, and keyboard use.'],
            ['practice', 'Create a basic location request', 'Practice requesting location asynchronously and handling errors.'],
            ['practice', 'Practice displaying a destination', 'Show one destination card with clear landmark text.'],
            ['build', 'Design the navigation interface', 'Sketch/layout search + destination results for three routes.'],
            ['build', 'Implement campus location search', 'Let users pick or search among campus destinations.'],
            ['build', 'Implement navigation/result display', 'Render route steps or landmark guidance for a selection.'],
            ['submit', 'Submit the project', 'Package your prototype and write a short explanation.'],
            ['submit', 'Add a short project explanation', 'Explain how Async API and accessibility show up in your work.'],
            ['present', 'Prepare a short project demonstration', 'Plan a 2–3 minute demo of the three routes.'],
            ['evaluate', 'Teacher evaluates the project', 'Faculty reviews submission, score, and feedback.'],
        ] as $i => [$phase, $title, $description]) {
            MissionTask::query()->create([
                'mission_id' => $campus->id,
                'title' => $title,
                'description' => $description,
                'phase' => $phase,
                'position' => $i + 1,
                'is_required' => true,
            ]);
        }

        MissionResource::query()->create([
            'mission_id' => $campus->id,
            'title' => 'MDN Geolocation API',
            'type' => 'link',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API',
            'position' => 1,
        ]);

        MissionResource::query()->create([
            'mission_id' => $campus->id,
            'title' => 'MDN Accessibility Basics',
            'type' => 'article',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Accessibility',
            'position' => 2,
        ]);

        $progressService = app(\App\Services\MissionProgressService::class);
        $alexEnrollment = $progressService->start($mission, $alex);
        $discoverLearnPractice = $mission->tasks()
            ->whereIn('phase', ['discover', 'learn', 'practice'])
            ->orderBy('position')
            ->get();
        foreach ($discoverLearnPractice as $task) {
            $progressService->completeTask($alexEnrollment, $task, $alex);
            $alexEnrollment = $alexEnrollment->fresh(['taskProgress', 'mission.tasks']);
        }

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

        foreach ($students as $student) {
            app(FlexLearnRecommendationService::class)->generateFor($student);
        }

        $this->command?->info('Primary student login: student@parbato.test / password');
        $this->command?->info('Teacher: teacher@parbato.test / password');
        $this->command?->info('Admin: admin@parbato.test / password');
        $this->command?->info('10 demo students seeded (all password: password)');
        $this->command?->info('Classroom join code: JOIN201A');
        $this->command?->info('Live attendance code: PARBATO1');
    }
}
