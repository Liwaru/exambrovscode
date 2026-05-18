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
        User::factory()->create([
            'username' => 'guru',
            'password' => 'password',
            'role' => 'teacher',
        ]);

        User::factory()->create([
            'username' => 'siswa',
            'password' => 'password',
            'role' => 'student',
            'class_name' => 'XI IPA 1',
        ]);
    }
}
