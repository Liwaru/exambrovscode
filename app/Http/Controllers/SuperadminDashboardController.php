<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperadminDashboardController extends Controller
{
    public function index()
    {
        $summary = [
            'students' => DB::table('siswa')->count(),
            'teachers' => DB::table('guru')->count(),
            'exams' => DB::table('exam_sessions')->count(),
        ];

        return view('superadmin.dashboard', compact('summary'));
    }

    public function students()
    {
        $students = DB::table('siswa')
            ->leftJoin('users', 'users.id_user', '=', 'siswa.id_user')
            ->leftJoin('kelas', 'kelas.id_kelas', '=', 'siswa.id_kelas')
            ->select('siswa.*', 'users.username', 'users.status', 'kelas.nama_kelas')
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

        DB::transaction(function () use ($validated) {
            $kelasId = $this->kelasIdFor($validated['nama_kelas']);
            $username = $this->uniqueUsername($validated['nama_siswa']);

            $user = User::create([
                'username' => $username,
                'password' => $username,
                'role' => 'student',
                'class_name' => $validated['nama_kelas'],
                'level' => 1,
                'status' => 'aktif',
            ]);

            DB::table('siswa')->insert([
                'id_user' => $user->getKey(),
                'id_kelas' => $kelasId,
                'nama_siswa' => $validated['nama_siswa'],
            ]);
        });

        return redirect()->route('admin.students')->with('status', 'Siswa berhasil ditambahkan.');
    }

    public function teachers()
    {
        $teachers = DB::table('guru')
            ->leftJoin('users', 'users.id_user', '=', 'guru.id_user')
            ->select('guru.*', 'users.username', 'users.status')
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

        DB::transaction(function () use ($validated) {
            $username = $this->uniqueUsername($validated['nama_guru']);

            $user = User::create([
                'username' => $username,
                'password' => $username,
                'role' => 'teacher',
                'class_name' => null,
                'level' => 2,
                'status' => 'aktif',
            ]);

            DB::table('guru')->insert([
                'id_user' => $user->getKey(),
                'nama_guru' => $validated['nama_guru'],
                'mapel' => $validated['mapel'],
            ]);
        });

        return redirect()->route('admin.teachers')->with('status', 'Guru berhasil ditambahkan.');
    }

    public function exams()
    {
        $exams = DB::table('exam_sessions')
            ->leftJoin('users', 'users.id_user', '=', 'exam_sessions.teacher_id')
            ->leftJoin('guru', 'guru.id_user', '=', 'users.id_user')
            ->select('exam_sessions.*', 'guru.nama_guru', 'users.username as teacher_username')
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

        $subjects = [
            'Bahasa Indonesia',
            'English',
            'Mandarin',
            'Sejarah',
            'PPKN',
            'Pemrograman Web',
            'Pemrograman Bergerak',
            'Matematika',
        ];

        $classes = [
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

        return view('superadmin.exams.create', compact('teachers', 'subjects', 'classes'));
    }

    public function storeExam(Request $request)
    {
        $subjects = [
            'Bahasa Indonesia',
            'English',
            'Mandarin',
            'Sejarah',
            'PPKN',
            'Pemrograman Web',
            'Pemrograman Bergerak',
            'Matematika',
        ];

        $classes = [
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

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'in:'.implode(',', $subjects)],
            'teacher_id' => ['required', 'integer', 'exists:users,id_user'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'class_name' => ['required', 'string', 'max:100', 'in:'.implode(',', $classes)],
        ]);

        DB::table('exam_sessions')->insert([
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

        return redirect()->route('admin.exams')->with('status', 'Ujian berhasil ditambahkan.');
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
}
