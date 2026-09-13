<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('room_label')->nullable();
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->unsignedSmallInteger('rows')->default(5);
            $table->unsignedSmallInteger('cols')->default(6);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('classroom_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('desk_row')->nullable();
            $table->unsignedSmallInteger('desk_col')->nullable();
            $table->string('desk_label', 16)->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'user_id']);
        });

        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->enum('status', ['scheduled', 'live', 'ended'])->default('scheduled')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('attendance_code', 64)->nullable()->index();
            $table->timestamp('attendance_code_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['qr', 'manual', 'system'])->default('qr');
            $table->enum('status', ['present', 'late', 'absent', 'excused'])->default('present');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('checked_in_at')->useCurrent();
            $table->timestamps();

            $table->unique(['class_session_id', 'user_id']);
        });

        Schema::create('classroom_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['quiz', 'poll', 'task', 'discussion', 'announcement'])->default('task');
            $table->json('payload')->nullable();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('help_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message')->nullable();
            $table->enum('status', ['open', 'acknowledged', 'resolved', 'cancelled'])->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('help_requests');
        Schema::dropIfExists('classroom_activities');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('class_sessions');
        Schema::dropIfExists('classroom_members');
        Schema::dropIfExists('classrooms');
    }
};
