<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('name');
            $table->text('description')->nullable()->after('subject');
            $table->string('join_code', 16)->nullable()->after('description');
            $table->enum('status', ['active', 'archived'])->default('active')->after('cols');
        });

        // Unique index separately so existing rows can be backfilled first in app/seeder.
        Schema::table('classrooms', function (Blueprint $table) {
            $table->unique('join_code');
        });

        Schema::table('classroom_members', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('desk_label');
            $table->timestamp('joined_at')->nullable()->after('status');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('checked_in_at');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->unsignedInteger('member_count_snapshot')->nullable()->after('notes');
            $table->unsignedInteger('present_count_snapshot')->nullable()->after('member_count_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropColumn(['member_count_snapshot', 'present_count_snapshot']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });

        Schema::table('classroom_members', function (Blueprint $table) {
            $table->dropColumn(['status', 'joined_at']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropUnique(['join_code']);
            $table->dropColumn(['subject', 'description', 'join_code', 'status']);
        });
    }
};
