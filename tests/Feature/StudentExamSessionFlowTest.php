<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExamSessionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_join_active_exam_session_with_entry_pin(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        ExamSession::create([
            'teacher_id' => $teacher->getKey(),
            'title' => 'Matematika',
            'class_name' => 'RPL XI',
            'exam_date' => now()->toDateString(),
            'entry_pin' => '123456',
            'status' => 'active',
        ]);

        $this->postJson('/api/student/exam-sessions/join', ['pin' => '123456'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Berhasil masuk ujian.')
            ->assertJsonPath('session.title', 'Matematika');
    }

    public function test_student_cannot_join_with_invalid_pin(): void
    {
        $this->postJson('/api/student/exam-sessions/join', ['pin' => '999999'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'PIN masuk tidak valid atau sesi belum aktif.');
    }
}
