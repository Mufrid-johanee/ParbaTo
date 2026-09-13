<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('student')->after('email')->index();
            $table->string('display_name')->nullable()->after('name');
            $table->string('avatar_url')->nullable()->after('display_name');
            $table->unsignedInteger('xp')->default(0)->after('avatar_url');
            $table->unsignedInteger('level')->default(1)->after('xp');
            $table->string('major')->nullable()->after('level');
            $table->text('bio')->nullable()->after('major');
            $table->string('firebase_uid')->nullable()->unique()->after('bio');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'display_name',
                'avatar_url',
                'xp',
                'level',
                'major',
                'bio',
                'firebase_uid',
                'deleted_at',
            ]);
        });
    }
};
