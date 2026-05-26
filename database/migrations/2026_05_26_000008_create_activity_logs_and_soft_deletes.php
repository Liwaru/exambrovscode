<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('actor_user_id')->nullable();
            $table->string('actor_username')->nullable();
            $table->tinyInteger('actor_level')->nullable();
            $table->string('event_type', 80);
            $table->string('description');
            $table->string('subject_table', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->boolean('recoverable')->default(false);
            $table->timestamp('restored_at')->nullable();
            $table->unsignedInteger('restored_by')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['subject_table', 'subject_id']);
            $table->index(['recoverable', 'restored_at']);
        });

        foreach (['users', 'siswa', 'guru', 'exam_sessions', 'exam_participants', 'nilai'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('deleted_at')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'siswa', 'guru', 'exam_sessions', 'exam_participants', 'nilai'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
            }
        }

        Schema::dropIfExists('activity_logs');
    }
};
