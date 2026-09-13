<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('max_attempts')->default(1)->after('pass_score');
            $table->timestamp('starts_at')->nullable()->after('max_attempts');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->text('description')->nullable()->after('title');
            $table->index(['status', 'classroom_id']);
            $table->index(['created_by', 'status']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('correct_answer');
        });

        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->timestamp('graded_at')->nullable()->after('submitted_at');
            $table->foreignId('graded_by')->nullable()->after('graded_at')->constrained('users')->nullOnDelete();
            $table->index(['assessment_id', 'user_id', 'status']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('selected_option')->nullable();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 5, 2)->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->timestamps();

            $table->unique(['assessment_attempt_id', 'question_id'], 'attempt_answers_unique');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('attempt_answers');

        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn('graded_at');
            $table->dropIndex(['assessment_id', 'user_id', 'status']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('classroom_id');
            $table->dropColumn(['max_attempts', 'starts_at', 'ends_at', 'description']);
        });
    }
};
