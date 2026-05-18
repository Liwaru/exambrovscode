<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use Illuminate\Http\Request;

class TeacherExamSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = ExamSession::query()
            ->where('teacher_id', $request->user()->getKey())
            ->withCount('participants')
            ->orderByDesc('exam_date')
            ->get();

        $summary = [
            'total' => $sessions->count(),
            'active' => $sessions->where('status', 'active')->count(),
            'synced' => $sessions->whereNotNull('external_exam_id')->count(),
        ];

        return view('teacher.dashboard', compact('sessions', 'summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:100'],
            'exam_date' => ['required', 'date'],
        ]);

        $request->user()->supervisedExamSessions()->create($validated);

        return redirect()->route('teacher.dashboard')->with('status', 'Sesi ujian berhasil dibuat.');
    }

    public function show(Request $request, ExamSession $examSession)
    {
        $this->authorizeTeacher($request, $examSession);

        $examSession->load(['participants.student']);

        return view('teacher.exam-session-show', [
            'session' => $examSession,
        ]);
    }

    public function generateEntryPin(Request $request, ExamSession $examSession)
    {
        $this->authorizeTeacher($request, $examSession);

        $examSession->update([
            'entry_pin' => $this->generatePin(),
            'status' => 'active',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'PIN masuk berhasil dibuat.',
                'pin' => $examSession->entry_pin,
                'status' => $examSession->status,
            ]);
        }

        return back()->with('status', 'PIN masuk berhasil dibuat.');
    }

    public function generateExitPin(Request $request, ExamSession $examSession)
    {
        $this->authorizeTeacher($request, $examSession);

        $examSession->update([
            'exit_pin' => $this->generatePin(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'PIN keluar berhasil dibuat.',
                'pin' => $examSession->exit_pin,
            ]);
        }

        return back()->with('status', 'PIN keluar berhasil dibuat.');
    }

    private function authorizeTeacher(Request $request, ExamSession $examSession): void
    {
        abort_unless($examSession->teacher_id === $request->user()->getKey(), 403);
    }

    private function generatePin(): string
    {
        return (string) random_int(100000, 999999);
    }
}
