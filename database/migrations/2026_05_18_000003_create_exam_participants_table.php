<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_session_id');
            $table->unsignedInteger('student_id');
            $table->string('status')->default('not_joined');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('rejoin_allowed')->default(false);
            $table->timestamps();

            $table->unique(['exam_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_participants');
    }
};
