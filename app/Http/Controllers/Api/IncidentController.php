<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamParticipant;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'exam_session_id' => ['nullable', 'integer', 'exists:exam_sessions,id'],
        ]);

        $query = ExamParticipant::query()
            ->with(['student', 'session'])
            ->where('status', 'interrupted')
            ->latest('left_at');

        if (! empty($validated['exam_session_id'])) {
            $query->where('exam_session_id', $validated['exam_session_id']);
        }

        return response()->json([
            'incidents' => $query->get()->map(fn (ExamParticipant $participant) => [
                'student_id' => $participant->student_id,
                'student_username' => $participant->student?->username,
                'exam_session_id' => $participant->exam_session_id,
                'exam_title' => $participant->session?->title,
                'occurred_at' => $participant->left_at?->toISOString(),
            ]),
        ]);
    }
}
