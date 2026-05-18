<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SuperadminDashboardController extends Controller
{
    public function index()
    {
        $summary = [
            'students' => DB::table('siswa')->count(),
            'teachers' => DB::table('guru')->count(),
            'exams' => DB::table('exam_sessions')->count(),
        ];

        $students = DB::table('siswa')
            ->leftJoin('users', 'users.id_user', '=', 'siswa.id_user')
            ->leftJoin('kelas', 'kelas.id_kelas', '=', 'siswa.id_kelas')
            ->select('siswa.*', 'users.username', 'kelas.nama_kelas')
            ->limit(5)
            ->get();

        $teachers = DB::table('guru')
            ->leftJoin('users', 'users.id_user', '=', 'guru.id_user')
            ->select('guru.*', 'users.username')
            ->limit(5)
            ->get();

        $exams = DB::table('exam_sessions')
            ->orderByDesc('exam_date')
            ->limit(5)
            ->get();

        return view('superadmin.dashboard', compact('summary', 'students', 'teachers', 'exams'));
    }
}
