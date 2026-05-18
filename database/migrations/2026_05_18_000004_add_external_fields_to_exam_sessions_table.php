<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('external_source')->nullable()->after('teacher_id');
            $table->string('external_exam_id')->nullable()->after('external_source');
            $table->string('callback_url')->nullable()->after('status');
            $table->unique(['external_source', 'external_exam_id']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropUnique(['external_source', 'external_exam_id']);
            $table->dropColumn(['external_source', 'external_exam_id', 'callback_url']);
        });
    }
};
