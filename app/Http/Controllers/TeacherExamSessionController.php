<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use Illuminate\Http\Request;

class TeacherExamSessionController extends Controller
{
    private const ACTIVITY_LOGS_PER_PAGE = 15;

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

        $activityLogs = $examSession->activityLogs()
            ->latest('occurred_at')
            ->paginate(self::ACTIVITY_LOGS_PER_PAGE)
            ->withQueryString();

        $recentActivityLogs = $examSession->activityLogs()
            ->latest('occurred_at')
            ->limit(100)
            ->get();

        $lastSeenByDevice = $recentActivityLogs
            ->whereNotNull('device_id')
            ->groupBy('device_id')
            ->map(fn ($logs) => $logs->sortByDesc('occurred_at')->first());

        return view('teacher.exam-session-show', [
            'session' => $examSession,
            'activityLogs' => $activityLogs,
            'lastSeenByDevice' => $lastSeenByDevice,
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

    public function activityLogs(Request $request, ExamSession $examSession)
    {
        $this->authorizeTeacher($request, $examSession);

        return response()->json($this->activityPayload($examSession, $request->integer('page', 1)));
    }

    public function activityStream(Request $request, ExamSession $examSession)
    {
        $this->authorizeTeacher($request, $examSession);

        return response()->stream(function () use ($examSession) {
            $lastActivityId = null;
            $startedAt = time();

            while (! connection_aborted() && time() - $startedAt < 120) {
                $latestActivityId = $examSession->activityLogs()->max('id');

                if ($latestActivityId !== $lastActivityId) {
                    $lastActivityId = $latestActivityId;
                    echo "event: activity\n";
                    echo 'data: '.json_encode($this->activityPayload($examSession))."\n\n";
                    ob_flush();
                    flush();
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function activityPayload(ExamSession $examSession, int $page = 1): array
    {
        $recentActivityLogs = $examSession->activityLogs()
            ->latest('occurred_at')
            ->limit(100)
            ->get();

        $activityLogs = $examSession->activityLogs()
            ->latest('occurred_at')
            ->paginate(self::ACTIVITY_LOGS_PER_PAGE, ['*'], 'page', max(1, $page));

        $lastSeenByDevice = $recentActivityLogs
            ->whereNotNull('device_id')
            ->groupBy('device_id')
            ->map(fn ($logs) => $logs->sortByDesc('occurred_at')->first())
            ->values();

        return [
            'devices' => $lastSeenByDevice->map(fn ($activity) => [
                'student_username' => $activity->student_username,
                'device_label' => $activity->device_name ?: $activity->device_id,
                'occurred_at' => $activity->occurred_at?->format('d-m-Y H:i:s'),
                'relative_time' => $activity->occurred_at?->diffForHumans(),
                'is_online' => ($activity->occurred_at?->diffInSeconds(now()) ?? 999999) <= 90,
            ])->values(),
            'logs' => $activityLogs->getCollection()->map(fn ($activity) => [
                'occurred_at' => $activity->occurred_at?->format('d-m-Y H:i:s'),
                'relative_time' => $activity->occurred_at?->diffForHumans(),
                'student_username' => $activity->student_username,
                'device_label' => $activity->device_name ?: $activity->device_id,
                'event_type' => $activity->event_type,
                'message' => $activity->message,
            ])->values(),
            'pagination' => [
                'current_page' => $activityLogs->currentPage(),
                'last_page' => $activityLogs->lastPage(),
                'per_page' => $activityLogs->perPage(),
                'total' => $activityLogs->total(),
                'from' => $activityLogs->firstItem(),
                'to' => $activityLogs->lastItem(),
            ],
        ];
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
