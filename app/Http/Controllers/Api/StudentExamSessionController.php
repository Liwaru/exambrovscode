<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class StudentExamSessionController extends Controller
{
    public function join(Request $request)
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        $session = ExamSession::query()
            ->where('entry_pin', $validated['pin'])
            ->where('status', 'active')
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'PIN masuk tidak valid atau sesi belum aktif.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil masuk ujian.',
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'class_name' => $session->class_name,
                'exam_date' => $session->exam_date->toDateString(),
            ],
        ]);
    }

    public function heartbeat(ExamSession $examSession)
    {
        return response()->json([
            'success' => true,
            'message' => 'Heartbeat diterima.',
            'session_id' => $examSession->id,
        ]);
    }

    public function exit(Request $request, ExamSession $examSession)
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        if ($validated['pin'] !== $examSession->exit_pin) {
            return response()->json([
                'success' => false,
                'message' => 'PIN keluar salah.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'PIN keluar benar. Siswa boleh menutup aplikasi.',
        ]);
    }

    public function markInterrupted(ExamSession $examSession)
    {
        return response()->json([
            'success' => true,
            'message' => 'Peringatan keluar paksa tercatat.',
            'session_id' => $examSession->id,
        ]);
    }
}
