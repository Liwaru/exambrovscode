<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->tinyInteger('level')->default(1)->after('class_name');
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'student')
                ->update(['level' => 1]);

            DB::table('users')
                ->where('role', 'teacher')
                ->update(['level' => 2]);

            DB::table('users')
                ->whereIn('role', ['admin', 'superadmin'])
                ->update(['level' => 3]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('student')->after('password');
            });
        }

        DB::table('users')
            ->where('level', 1)
            ->update(['role' => 'student']);

        DB::table('users')
            ->where('level', 2)
            ->update(['role' => 'teacher']);

        DB::table('users')
            ->where('level', 3)
            ->update(['role' => 'admin']);
    }
};
