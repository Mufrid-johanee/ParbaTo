<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('domain')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('objective')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner')->index();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('xp_reward')->default(100);
            $table->enum('mode', ['individual', 'team'])->default('individual');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->timestamp('deadline_at')->nullable();
            $table->json('assessment_criteria')->nullable();
            $table->json('submission_requirements')->nullable();
            $table->json('completion_criteria')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mission_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unique(['mission_id', 'skill_id']);
        });

        Schema::create('mission_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('phase', ['discover', 'learn', 'practice', 'build', 'submit', 'present', 'evaluate'])->default('learn');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        Schema::create('mission_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['link', 'file', 'video', 'article'])->default('link');
            $table->string('url')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('mission_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['discovered', 'in_progress', 'submitted', 'evaluated', 'completed'])->default('discovered')->index();
            $table->enum('lifecycle_phase', ['discover', 'learn', 'practice', 'build', 'submit', 'present', 'evaluate'])->default('discover');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['mission_id', 'user_id']);
        });

        Schema::create('mission_task_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_task_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['mission_enrollment_id', 'mission_task_id'], 'mission_task_progress_unique');
        });

        Schema::create('mission_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'submitted', 'returned', 'accepted'])->default('draft');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_submissions');
        Schema::dropIfExists('mission_task_progress');
        Schema::dropIfExists('mission_enrollments');
        Schema::dropIfExists('mission_resources');
        Schema::dropIfExists('mission_tasks');
        Schema::dropIfExists('mission_skill');
        Schema::dropIfExists('missions');
        Schema::dropIfExists('skills');
    }
};
