<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Http\Request;

class ExternalExamController extends Controller
{
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:50'],
            'external_exam_id' => ['required', 'string', 'max:100'],
            'teacher_username' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:100'],
            'exam_date' => ['required', 'date'],
            'callback_url' => ['nullable', 'url'],
        ]);

        $teacher = User::query()
            ->where('username', $validated['teacher_username'])
            ->where('role', 'teacher')
            ->firstOrFail();

        $session = ExamSession::updateOrCreate(
            [
                'external_source' => $validated['source'],
                'external_exam_id' => $validated['external_exam_id'],
            ],
            [
                'teacher_id' => $teacher->getKey(),
                'title' => $validated['title'],
                'class_name' => $validated['class_name'],
                'exam_date' => $validated['exam_date'],
                'callback_url' => $validated['callback_url'] ?? null,
            ],
        );

        return response()->json([
            'message' => 'Ujian berhasil disinkronkan ke Exambro.',
            'exam_session_id' => $session->id,
        ]);
    }
}
