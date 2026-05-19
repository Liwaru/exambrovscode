<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('student_id')->nullable();
            $table->string('student_username')->nullable();
            $table->string('device_id', 80)->nullable();
            $table->string('device_name')->nullable();
            $table->string('event_type', 80);
            $table->string('message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['exam_session_id', 'occurred_at']);
            $table->index(['exam_session_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_activity_logs');
    }
};
