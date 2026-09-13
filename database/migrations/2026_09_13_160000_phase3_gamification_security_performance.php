<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'source_type', 'source_id'], 'xp_ledger_unique_event');
            $table->index(['user_id', 'awarded_at']);
            $table->index('source_type');
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->string('category')->nullable()->after('icon');
            $table->json('criteria')->nullable()->after('category');
            $table->boolean('is_active')->default(true)->after('criteria');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('user_agent', 512)->nullable()->after('ip_address');
            $table->index('action');
            $table->index('created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('read_at');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);
            $table->dropColumn('user_agent');
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['category', 'criteria', 'is_active']);
        });

        Schema::dropIfExists('xp_ledger');
    }
};
