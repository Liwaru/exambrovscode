<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()
            ->where('role', 'superadmin')
            ->delete();

        User::updateOrCreate(
            ['username' => 'pak dedi'],
            [
                'password' => 'pak dedi',
                'role' => 'admin',
                'class_name' => null,
                'level' => 3,
                'status' => 'aktif',
            ]
        );

        User::updateOrCreate(
            ['username' => 'guru'],
            [
                'password' => 'password',
                'role' => 'teacher',
                'class_name' => null,
                'level' => 2,
                'status' => 'aktif',
            ]
        );

        User::updateOrCreate(
            ['username' => 'siswa'],
            [
                'password' => 'password',
                'role' => 'student',
                'class_name' => 'RPL XI',
                'level' => 1,
                'status' => 'aktif',
            ]
        );
    }
}
