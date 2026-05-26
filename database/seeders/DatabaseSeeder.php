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
            ->where('level', User::LEVEL_ADMIN)
            ->where('username', 'superadmin')
            ->delete();

        User::updateOrCreate(
            ['username' => 'pak dedi'],
            [
                'password' => 'pak dedi',
                'class_name' => null,
                'level' => User::LEVEL_ADMIN,
                'status' => 'aktif',
            ]
        );

        User::updateOrCreate(
            ['username' => 'guru'],
            [
                'password' => 'password',
                'class_name' => null,
                'level' => User::LEVEL_TEACHER,
                'status' => 'aktif',
            ]
        );

        User::updateOrCreate(
            ['username' => 'siswa'],
            [
                'password' => 'password',
                'class_name' => 'RPL XI',
                'level' => User::LEVEL_STUDENT,
                'status' => 'aktif',
            ]
        );
    }
}
