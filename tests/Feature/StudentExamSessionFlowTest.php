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
            'level' => User::LEVEL_TEACHER,
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

    public function test_student_is_forced_out_when_session_is_inactive(): void
    {
        $teacher = User::factory()->create([
            'level' => User::LEVEL_TEACHER,
        ]);

        $session = ExamSession::create([
            'teacher_id' => $teacher->getKey(),
            'title' => 'Matematika',
            'class_name' => 'RPL XI',
            'exam_date' => now()->toDateString(),
            'entry_pin' => '123456',
            'status' => 'inactive',
        ]);

        $this->postJson("/api/student/exam-sessions/{$session->id}/heartbeat", [
            'student_username' => 'Budi',
            'device_id' => 'device-1',
        ])
            ->assertStatus(423)
            ->assertJsonPath('success', false)
            ->assertJsonPath('force_exit', true)
            ->assertJsonPath('message', 'Sesi ujian sudah dinonaktifkan oleh guru.');
    }

    public function test_teacher_can_activate_and_deactivate_exam_session(): void
    {
        $teacher = User::factory()->create([
            'level' => User::LEVEL_TEACHER,
        ]);

        $session = ExamSession::create([
            'teacher_id' => $teacher->getKey(),
            'title' => 'Matematika',
            'class_name' => 'RPL XI',
            'exam_date' => now()->toDateString(),
            'entry_pin' => '123456',
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->postJson("/teacher/exam-sessions/{$session->id}/status", [
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->assertSame('inactive', $session->fresh()->status);
    }

    public function test_teacher_can_delete_selected_owned_exam_sessions_only(): void
    {
        $teacher = User::factory()->create([
            'level' => User::LEVEL_TEACHER,
        ]);
        $otherTeacher = User::factory()->create([
            'level' => User::LEVEL_TEACHER,
        ]);

        $ownedSession = ExamSession::create([
            'teacher_id' => $teacher->getKey(),
            'title' => 'Matematika',
            'class_name' => 'RPL XI',
            'exam_date' => now()->toDateString(),
            'status' => 'inactive',
        ]);
        $otherSession = ExamSession::create([
            'teacher_id' => $otherTeacher->getKey(),
            'title' => 'Bahasa Indonesia',
            'class_name' => 'RPL XI',
            'exam_date' => now()->toDateString(),
            'status' => 'inactive',
        ]);

        $this->actingAs($teacher)
            ->delete('/teacher/exam-sessions', [
                'exam_session_ids' => [$ownedSession->id, $otherSession->id],
            ])
            ->assertRedirect(route('teacher.dashboard'));

        $this->assertSoftDeleted('exam_sessions', [
            'id' => $ownedSession->id,
        ]);
        $this->assertDatabaseHas('exam_sessions', [
            'id' => $otherSession->id,
        ]);
    }
}
