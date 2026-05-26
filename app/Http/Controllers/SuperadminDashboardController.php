<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperadminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->recordMenuOpened($request, 'Dashboard Admin');

        $summary = [
            'students' => DB::table('siswa')->whereNull('deleted_at')->count(),
            'teachers' => DB::table('guru')->whereNull('deleted_at')->count(),
            'exams' => DB::table('exam_sessions')->whereNull('deleted_at')->count(),
        ];

        return view('superadmin.dashboard', compact('summary'));
    }

    public function students()
    {
        $this->recordMenuOpened(request(), 'Data Siswa');

        $students = DB::table('siswa')
            ->leftJoin('users', 'users.id_user', '=', 'siswa.id_user')
            ->leftJoin('kelas', 'kelas.id_kelas', '=', 'siswa.id_kelas')
            ->select('siswa.*', 'users.username', 'users.status', 'kelas.nama_kelas')
            ->whereNull('siswa.deleted_at')
            ->orderBy('siswa.nama_siswa')
            ->get();

        return view('superadmin.students.index', compact('students'));
    }

    public function createStudent()
    {
        return view('superadmin.students.create');
    }

    public function storeStudent(Request $request)
    {
        $validated = $request->validate([
            'nama_siswa' => ['required', 'string', 'max:100'],
            'nama_kelas' => ['required', 'string', 'max:50'],
        ]);

        $studentId = null;

        DB::transaction(function () use ($validated, &$studentId) {
            $kelasId = $this->kelasIdFor($validated['nama_kelas']);
            $username = $this->uniqueUsername($validated['nama_siswa']);

            $user = User::create([
                'username' => $username,
                'password' => $username,
                'class_name' => $validated['nama_kelas'],
                'level' => User::LEVEL_STUDENT,
                'status' => 'aktif',
            ]);

            $studentId = DB::table('siswa')->insertGetId([
                'id_user' => $user->getKey(),
                'id_kelas' => $kelasId,
                'nama_siswa' => $validated['nama_siswa'],
            ]);
        });

        ActivityLog::record('student_created', "Data siswa {$validated['nama_siswa']} ditambahkan.", $request, [
            'subject_table' => 'siswa',
            'subject_id' => $studentId,
        ]);

        return redirect()->route('admin.students')->with('status', 'Siswa berhasil ditambahkan.');
    }

    public function editStudent(int $student)
    {
        $student = DB::table('siswa')
            ->leftJoin('users', 'users.id_user', '=', 'siswa.id_user')
            ->leftJoin('kelas', 'kelas.id_kelas', '=', 'siswa.id_kelas')
            ->select('siswa.*', 'users.username', 'users.status', 'kelas.nama_kelas')
            ->where('siswa.id_siswa', $student)
            ->whereNull('siswa.deleted_at')
            ->first();

        abort_unless($student, 404);

        return view('superadmin.students.edit', compact('student'));
    }

    public function updateStudent(Request $request, int $student)
    {
        $student = DB::table('siswa')->where('id_siswa', $student)->whereNull('deleted_at')->first();
        abort_unless($student, 404);

        $validated = $request->validate([
            'nama_siswa' => ['required', 'string', 'max:100'],
            'nama_kelas' => ['required', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($student, $validated) {
            $kelasId = $this->kelasIdFor($validated['nama_kelas']);

            DB::table('siswa')
                ->where('id_siswa', $student->id_siswa)
                ->update([
                    'id_kelas' => $kelasId,
                    'nama_siswa' => $validated['nama_siswa'],
                ]);

            DB::table('users')
                ->where('id_user', $student->id_user)
                ->update([
                    'class_name' => $validated['nama_kelas'],
                ]);
        });

        ActivityLog::record('student_updated', "Data siswa {$validated['nama_siswa']} diperbarui.", $request, [
            'subject_table' => 'siswa',
            'subject_id' => $student->id_siswa,
        ]);

        return redirect()->route('admin.students')->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function destroyStudent(Request $request, int $student)
    {
        $student = DB::table('siswa')->where('id_siswa', $student)->whereNull('deleted_at')->first();
        abort_unless($student, 404);

        DB::transaction(function () use ($student) {
            DB::table('siswa')->where('id_siswa', $student->id_siswa)->update(['deleted_at' => now()]);
            DB::table('users')->where('id_user', $student->id_user)->update(['deleted_at' => now()]);
        });

        ActivityLog::record('student_deleted', "Data siswa {$student->nama_siswa} dihapus dan bisa dipulihkan.", $request, [
            'subject_table' => 'siswa',
            'subject_id' => $student->id_siswa,
            'recoverable' => true,
        ]);

        return redirect()->route('admin.students')->with('status', 'Data siswa berhasil dihapus dan bisa dipulihkan dari Catatan Aktivitas.');
    }

    public function teachers()
    {
        $this->recordMenuOpened(request(), 'Data Guru');

        $teachers = DB::table('guru')
            ->leftJoin('users', 'users.id_user', '=', 'guru.id_user')
            ->select('guru.*', 'users.username', 'users.status')
            ->whereNull('guru.deleted_at')
            ->orderBy('guru.nama_guru')
            ->get();

        return view('superadmin.teachers.index', compact('teachers'));
    }

    public function createTeacher()
    {
        return view('superadmin.teachers.create');
    }

    public function storeTeacher(Request $request)
    {
        $validated = $request->validate([
            'nama_guru' => ['required', 'string', 'max:100'],
            'mapel' => ['required', 'string', 'max:100'],
        ]);

        $teacherId = null;

        DB::transaction(function () use ($validated, &$teacherId) {
            $username = $this->uniqueUsername($validated['nama_guru']);

            $user = User::create([
                'username' => $username,
                'password' => $username,
                'class_name' => null,
                'level' => User::LEVEL_TEACHER,
                'status' => 'aktif',
            ]);

            $teacherId = DB::table('guru')->insertGetId([
                'id_user' => $user->getKey(),
                'nama_guru' => $validated['nama_guru'],
                'mapel' => $validated['mapel'],
            ]);
        });

        ActivityLog::record('teacher_created', "Data guru {$validated['nama_guru']} ditambahkan.", $request, [
            'subject_table' => 'guru',
            'subject_id' => $teacherId,
        ]);

        return redirect()->route('admin.teachers')->with('status', 'Guru berhasil ditambahkan.');
    }

    public function editTeacher(int $teacher)
    {
        $teacher = DB::table('guru')
            ->leftJoin('users', 'users.id_user', '=', 'guru.id_user')
            ->select('guru.*', 'users.username', 'users.status')
            ->where('guru.id_guru', $teacher)
            ->whereNull('guru.deleted_at')
            ->first();

        abort_unless($teacher, 404);

        return view('superadmin.teachers.edit', compact('teacher'));
    }

    public function updateTeacher(Request $request, int $teacher)
    {
        $teacher = DB::table('guru')->where('id_guru', $teacher)->whereNull('deleted_at')->first();
        abort_unless($teacher, 404);

        $validated = $request->validate([
            'nama_guru' => ['required', 'string', 'max:100'],
            'mapel' => ['required', 'string', 'max:100'],
        ]);

        DB::table('guru')
            ->where('id_guru', $teacher->id_guru)
            ->update([
                'nama_guru' => $validated['nama_guru'],
                'mapel' => $validated['mapel'],
            ]);

        ActivityLog::record('teacher_updated', "Data guru {$validated['nama_guru']} diperbarui.", $request, [
            'subject_table' => 'guru',
            'subject_id' => $teacher->id_guru,
        ]);

        return redirect()->route('admin.teachers')->with('status', 'Data guru berhasil diperbarui.');
    }

    public function destroyTeacher(Request $request, int $teacher)
    {
        $teacher = DB::table('guru')->where('id_guru', $teacher)->whereNull('deleted_at')->first();
        abort_unless($teacher, 404);

        DB::transaction(function () use ($teacher) {
            DB::table('guru')->where('id_guru', $teacher->id_guru)->update(['deleted_at' => now()]);
            DB::table('users')->where('id_user', $teacher->id_user)->update(['deleted_at' => now()]);
        });

        ActivityLog::record('teacher_deleted', "Data guru {$teacher->nama_guru} dihapus dan bisa dipulihkan.", $request, [
            'subject_table' => 'guru',
            'subject_id' => $teacher->id_guru,
            'recoverable' => true,
        ]);

        return redirect()->route('admin.teachers')->with('status', 'Data guru berhasil dihapus dan bisa dipulihkan dari Catatan Aktivitas.');
    }

    public function exams()
    {
        $this->recordMenuOpened(request(), 'Data Ujian');

        $exams = DB::table('exam_sessions')
            ->leftJoin('users', 'users.id_user', '=', 'exam_sessions.teacher_id')
            ->leftJoin('guru', 'guru.id_user', '=', 'users.id_user')
            ->select('exam_sessions.*', 'guru.nama_guru', 'users.username as teacher_username')
            ->whereNull('exam_sessions.deleted_at')
            ->orderByDesc('exam_sessions.exam_date')
            ->orderBy('exam_sessions.start_time')
            ->get();

        return view('superadmin.exams.index', compact('exams'));
    }

    public function createExam()
    {
        $teachers = DB::table('guru')
            ->leftJoin('users', 'users.id_user', '=', 'guru.id_user')
            ->select('guru.*', 'users.username')
            ->orderBy('guru.nama_guru')
            ->get();

        $subjects = $this->subjects();
        $classes = $this->classes();

        return view('superadmin.exams.create', compact('teachers', 'subjects', 'classes'));
    }

    public function storeExam(Request $request)
    {
        $subjects = $this->subjects();
        $classes = $this->classes();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'in:'.implode(',', $subjects)],
            'teacher_id' => ['required', 'integer', 'exists:users,id_user'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'class_name' => ['required', 'string', 'max:100', 'in:'.implode(',', $classes)],
        ]);

        $examId = DB::table('exam_sessions')->insertGetId([
            'teacher_id' => $validated['teacher_id'],
            'title' => $validated['title'],
            'class_name' => $validated['class_name'],
            'exam_date' => $validated['exam_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ActivityLog::record('exam_created', "Data ujian {$validated['title']} ditambahkan.", $request, [
            'subject_table' => 'exam_sessions',
            'subject_id' => $examId,
        ]);

        return redirect()->route('admin.exams')->with('status', 'Ujian berhasil ditambahkan.');
    }

    public function editExam(int $exam)
    {
        $exam = DB::table('exam_sessions')->where('id', $exam)->whereNull('deleted_at')->first();
        abort_unless($exam, 404);

        $teachers = DB::table('guru')
            ->leftJoin('users', 'users.id_user', '=', 'guru.id_user')
            ->select('guru.*', 'users.username')
            ->orderBy('guru.nama_guru')
            ->get();
        $subjects = $this->subjects();
        $classes = $this->classes();

        return view('superadmin.exams.edit', compact('exam', 'teachers', 'subjects', 'classes'));
    }

    public function updateExam(Request $request, int $exam)
    {
        $exam = DB::table('exam_sessions')->where('id', $exam)->whereNull('deleted_at')->first();
        abort_unless($exam, 404);

        $subjects = $this->subjects();
        $classes = $this->classes();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'in:'.implode(',', $subjects)],
            'teacher_id' => ['required', 'integer', 'exists:users,id_user'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'class_name' => ['required', 'string', 'max:100', 'in:'.implode(',', $classes)],
        ]);

        DB::table('exam_sessions')
            ->where('id', $exam->id)
            ->update([
                'teacher_id' => $validated['teacher_id'],
                'title' => $validated['title'],
                'class_name' => $validated['class_name'],
                'exam_date' => $validated['exam_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'updated_at' => now(),
            ]);

        ActivityLog::record('exam_updated', "Data ujian {$validated['title']} diperbarui.", $request, [
            'subject_table' => 'exam_sessions',
            'subject_id' => $exam->id,
        ]);

        return redirect()->route('admin.exams')->with('status', 'Data ujian berhasil diperbarui.');
    }

    public function destroyExam(Request $request, int $exam)
    {
        $exam = DB::table('exam_sessions')->where('id', $exam)->whereNull('deleted_at')->first();
        abort_unless($exam, 404);

        DB::table('exam_sessions')->where('id', $exam->id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        ActivityLog::record('exam_deleted', "Data ujian {$exam->title} dihapus dan bisa dipulihkan.", $request, [
            'subject_table' => 'exam_sessions',
            'subject_id' => $exam->id,
            'recoverable' => true,
        ]);

        return redirect()->route('admin.exams')->with('status', 'Data ujian berhasil dihapus dan bisa dipulihkan dari Catatan Aktivitas.');
    }

    public function activityLogs(Request $request)
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isHeadmaster(), 403);

        $this->recordMenuOpened($request, 'Catatan Aktivitas');

        $dataEventTypes = $this->dataActivityEventTypes();
        $activeTab = $request->query('tab') === 'data' ? 'data' : 'aktivitas';

        $activityLogs = ActivityLog::query()
            ->with(['actor', 'restoredByUser'])
            ->whereNotIn('event_type', $dataEventTypes)
            ->latest()
            ->paginate(20, ['*'], 'activity_page')
            ->appends(['tab' => 'aktivitas']);

        $dataLogs = ActivityLog::query()
            ->with(['actor', 'restoredByUser'])
            ->whereIn('event_type', $dataEventTypes)
            ->latest()
            ->paginate(20, ['*'], 'data_page')
            ->appends(['tab' => 'data']);

        $recoverableLogs = ActivityLog::query()
            ->where('recoverable', true)
            ->whereNull('restored_at')
            ->latest()
            ->get()
            ->filter(fn (ActivityLog $log) => $this->isStillDeleted($log))
            ->values();

        return view('superadmin.activity-logs.index', compact('activeTab', 'activityLogs', 'dataLogs', 'recoverableLogs'));
    }

    public function restoreActivity(Request $request, ActivityLog $activityLog)
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isHeadmaster(), 403);
        abort_unless($activityLog->recoverable && ! $activityLog->restored_at, 404);

        $restored = match ($activityLog->subject_table) {
            'siswa' => $this->restoreStudentRecord((int) $activityLog->subject_id),
            'guru' => $this->restoreTeacherRecord((int) $activityLog->subject_id),
            'exam_sessions' => $this->restoreExamRecord((int) $activityLog->subject_id),
            'exam_participants' => $this->restoreSimpleRecord('exam_participants', 'id', (int) $activityLog->subject_id),
            'nilai' => $this->restoreSimpleRecord('nilai', 'id_nilai', (int) $activityLog->subject_id),
            default => false,
        };

        abort_unless($restored, 404);

        $activityLog->update([
            'restored_at' => now(),
            'restored_by' => $request->user()->getKey(),
        ]);

        ActivityLog::record('data_restored', "Data dari log #{$activityLog->id} dipulihkan.", $request, [
            'subject_table' => $activityLog->subject_table,
            'subject_id' => $activityLog->subject_id,
        ]);

        return redirect()->route('admin.activity-logs')->with('status', 'Data berhasil dipulihkan.');
    }

    private function subjects(): array
    {
        return [
            'Bahasa Indonesia',
            'English',
            'Mandarin',
            'Sejarah',
            'PPKN',
            'Pemrograman Web',
            'Pemrograman Bergerak',
            'Matematika',
        ];
    }

    private function classes(): array
    {
        return [
            '7A',
            '7B',
            '7C',
            '8A',
            '8B',
            '8C',
            '9A',
            '9B',
            '9C',
            'RPL X',
            'RPL XI',
            'RPL XII',
            'BDP X',
            'BDP XI',
            'BDP XII',
            'AKL X',
            'AKL XI',
            'AKL XII',
        ];
    }

    private function kelasIdFor(string $namaKelas): int
    {
        DB::table('kelas')->updateOrInsert(
            ['nama_kelas' => $namaKelas],
            ['jurusan' => null]
        );

        return (int) DB::table('kelas')
            ->where('nama_kelas', $namaKelas)
            ->value('id_kelas');
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::of($name)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value() ?: 'user';

        $username = $base;
        $counter = 2;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'.'.$counter;
            $counter++;
        }

        return $username;
    }

    private function recordMenuOpened(Request $request, string $menuName): void
    {
        ActivityLog::record('menu_opened', "{$request->user()?->username} membuka menu {$menuName}.", $request, [
            'properties' => ['menu' => $menuName],
        ]);
    }

    private function dataActivityEventTypes(): array
    {
        return [
            'student_created',
            'student_updated',
            'student_deleted',
            'teacher_created',
            'teacher_updated',
            'teacher_deleted',
            'exam_created',
            'exam_updated',
            'exam_deleted',
            'teacher_exam_created',
            'teacher_exam_deleted',
            'data_restored',
            'database_backup_created',
            'database_imported',
            'database_reset',
        ];
    }

    private function isStillDeleted(ActivityLog $log): bool
    {
        if (! $log->subject_table || ! DB::getSchemaBuilder()->hasTable($log->subject_table)) {
            return false;
        }

        return match ($log->subject_table) {
            'siswa' => DB::table('siswa')->where('id_siswa', $log->subject_id)->whereNotNull('deleted_at')->exists(),
            'guru' => DB::table('guru')->where('id_guru', $log->subject_id)->whereNotNull('deleted_at')->exists(),
            'exam_sessions' => DB::table('exam_sessions')->where('id', $log->subject_id)->whereNotNull('deleted_at')->exists(),
            'exam_participants' => DB::table('exam_participants')->where('id', $log->subject_id)->whereNotNull('deleted_at')->exists(),
            'nilai' => DB::table('nilai')->where('id_nilai', $log->subject_id)->whereNotNull('deleted_at')->exists(),
            default => false,
        };
    }

    private function restoreStudentRecord(int $studentId): bool
    {
        $student = DB::table('siswa')->where('id_siswa', $studentId)->whereNotNull('deleted_at')->first();

        if (! $student) {
            return false;
        }

        DB::transaction(function () use ($student) {
            DB::table('siswa')->where('id_siswa', $student->id_siswa)->update(['deleted_at' => null]);
            DB::table('users')->where('id_user', $student->id_user)->update(['deleted_at' => null]);
        });

        return true;
    }

    private function restoreTeacherRecord(int $teacherId): bool
    {
        $teacher = DB::table('guru')->where('id_guru', $teacherId)->whereNotNull('deleted_at')->first();

        if (! $teacher) {
            return false;
        }

        DB::transaction(function () use ($teacher) {
            DB::table('guru')->where('id_guru', $teacher->id_guru)->update(['deleted_at' => null]);
            DB::table('users')->where('id_user', $teacher->id_user)->update(['deleted_at' => null]);
        });

        return true;
    }

    private function restoreExamRecord(int $examId): bool
    {
        $updated = DB::table('exam_sessions')
            ->where('id', $examId)
            ->whereNotNull('deleted_at')
            ->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }

    private function restoreSimpleRecord(string $table, string $key, int $id): bool
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return false;
        }

        $updated = DB::table($table)
            ->where($key, $id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        return $updated > 0;
    }
}
