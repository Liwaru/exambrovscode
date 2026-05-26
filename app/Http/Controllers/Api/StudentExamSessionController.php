<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamActivityLog;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentExamSessionController extends Controller
{
    public function join(Request $request)
    {
        $validated = $request->validate([
            'student_username' => ['nullable', 'string', 'max:255'],
            'pin' => ['required', 'digits:6'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'device_name' => ['nullable', 'string', 'max:255'],
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

        $alreadyJoined = false;

        if (! empty($validated['device_id']) || ! empty($validated['student_username'])) {
            $alreadyJoined = ExamActivityLog::query()
                ->where('exam_session_id', $session->id)
                ->where('event_type', 'joined')
                ->where(function ($query) use ($validated) {
                    if (! empty($validated['device_id'])) {
                        $query->orWhere('device_id', $validated['device_id']);
                    }

                    if (! empty($validated['student_username'])) {
                        $query->orWhere('student_username', $validated['student_username']);
                    }
                })
                ->exists();
        }

        if ($alreadyJoined) {
            return response()->json([
                'success' => false,
                'message' => 'PIN masuk sudah pernah dipakai oleh siswa/perangkat ini.',
            ], 422);
        }

        $this->logActivity($request, $session, 'joined', 'Siswa masuk ujian dengan PIN masuk.');

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
        if ($examSession->status !== 'active') {
            return $this->forceExitResponse(request(), $examSession);
        }

        $this->logActivity(request(), $examSession, 'heartbeat', 'Aplikasi siswa masih aktif.');

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
            'student_username' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $alreadyExited = false;

        if (! empty($validated['device_id']) || ! empty($validated['student_username'])) {
            $alreadyExited = ExamActivityLog::query()
                ->where('exam_session_id', $examSession->id)
                ->where('event_type', 'exited')
                ->where(function ($query) use ($validated) {
                if (! empty($validated['device_id'])) {
                    $query->orWhere('device_id', $validated['device_id']);
                }

                if (! empty($validated['student_username'])) {
                    $query->orWhere('student_username', $validated['student_username']);
                }
                })
                ->exists();
        }

        if ($alreadyExited) {
            return response()->json([
                'success' => false,
                'message' => 'PIN keluar untuk siswa/perangkat ini sudah pernah dipakai.',
            ], 422);
        }

        if ($validated['pin'] !== $examSession->exit_pin) {
            $this->logActivity($request, $examSession, 'exit_pin_failed', 'Siswa salah memasukkan PIN keluar.');

            return response()->json([
                'success' => false,
                'message' => 'PIN keluar salah.',
            ], 422);
        }

        $this->logActivity($request, $examSession, 'exited', 'Siswa keluar ujian dengan PIN keluar yang benar.');

        return response()->json([
            'success' => true,
            'message' => 'PIN keluar benar. Siswa boleh menutup aplikasi.',
        ]);
    }

    public function markInterrupted(ExamSession $examSession)
    {
        $this->logActivity(request(), $examSession, 'interrupted', 'Aplikasi mendeteksi percobaan keluar paksa.');

        return response()->json([
            'success' => true,
            'message' => 'Peringatan keluar paksa tercatat.',
            'session_id' => $examSession->id,
        ]);
    }

    public function activity(Request $request, ExamSession $examSession)
    {
        if ($examSession->status !== 'active') {
            return $this->forceExitResponse($request, $examSession);
        }

        $validated = $request->validate([
            'event_type' => ['required', 'string', 'max:80', Rule::in([
                'app_reopened',
                'app_backgrounded',
                'app_closed',
                'heartbeat',
                'interrupted',
                'student_identified',
                'elearning_page_loaded',
                'back_pressed',
                'home_pressed',
                'exit_button_pressed',
            ])],
            'message' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'student_id' => ['nullable', 'integer'],
            'student_username' => ['nullable', 'string', 'max:255'],
        ]);

        $this->logActivity(
            $request,
            $examSession,
            $validated['event_type'],
            $validated['message'] ?? null
        );

        if ($validated['event_type'] === 'student_identified' && ! empty($validated['student_username']) && ! empty($validated['device_id'])) {
            ExamActivityLog::query()
                ->where('exam_session_id', $examSession->id)
                ->where('device_id', $validated['device_id'])
                ->whereNull('student_username')
                ->update(['student_username' => $validated['student_username']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas tercatat.',
        ]);
    }

    private function forceExitResponse(Request $request, ExamSession $examSession)
    {
        $alreadyLogged = ExamActivityLog::query()
            ->where('exam_session_id', $examSession->id)
            ->where('event_type', 'forced_exit')
            ->where(function ($query) use ($request) {
                $deviceId = $request->input('device_id');
                $studentUsername = $request->input('student_username');

                if ($deviceId) {
                    $query->orWhere('device_id', $deviceId);
                }

                if ($studentUsername) {
                    $query->orWhere('student_username', $studentUsername);
                }
            })
            ->exists();

        if (! $alreadyLogged) {
            $this->logActivity($request, $examSession, 'forced_exit', 'Sesi dinonaktifkan oleh guru. Aplikasi siswa dipaksa keluar.');
        }

        return response()->json([
            'success' => false,
            'force_exit' => true,
            'message' => 'Sesi ujian sudah dinonaktifkan oleh guru.',
            'session_id' => $examSession->id,
        ], 423);
    }

    private function logActivity(Request $request, ExamSession $examSession, string $eventType, ?string $message = null): void
    {
        $deviceId = $request->input('device_id');
        $studentId = $request->integer('student_id') ?: null;

        ExamActivityLog::create([
            'exam_session_id' => $examSession->id,
            'student_id' => $studentId,
            'student_username' => $request->input('student_username'),
            'device_id' => $deviceId,
            'device_name' => $request->input('device_name'),
            'event_type' => $eventType,
            'message' => $message,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'occurred_at' => now(),
        ]);
    }
}
