<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('evidence_type', 64); // mission_evaluation, assessment, mission_progress
            $table->decimal('score', 5, 2); // 0-100
            $table->unsignedTinyInteger('weight')->default(50); // relative weight 1-100
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'skill_id']);
            $table->index(['source_type', 'source_id']);
            $table->unique(
                ['user_id', 'skill_id', 'source_type', 'source_id', 'evidence_type'],
                'learning_evidences_unique_source'
            );
        });

        Schema::table('recommendations', function (Blueprint $table) {
            $table->unsignedTinyInteger('sort_order')->default(50)->after('priority');
        });

        // Expand recommendation lifecycle on MySQL installs created before `started` existed.
        // Fresh installs (and SQLite) already include `started` in the create migration.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE recommendations MODIFY status ENUM('active','started','completed','dismissed') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE recommendations MODIFY status ENUM('active','dismissed','completed') NOT NULL DEFAULT 'active'");
        }

        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::dropIfExists('learning_evidences');
    }
};
