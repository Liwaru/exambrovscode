<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperadminDashboardController;
use App\Http\Controllers\TeacherExamSessionController;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('auth.login');
    }

    return in_array(auth()->user()->role, ['admin', 'superadmin'], true)
        ? redirect()->route('admin.dashboard')
        : redirect()->route('teacher.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::redirect('/superadmin/dashboard', '/admin/dashboard');
    Route::get('/admin/dashboard', [SuperadminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/students', [SuperadminDashboardController::class, 'students'])->name('admin.students');
    Route::get('/admin/students/create', [SuperadminDashboardController::class, 'createStudent'])->name('admin.students.create');
    Route::post('/admin/students', [SuperadminDashboardController::class, 'storeStudent'])->name('admin.students.store');
    Route::get('/admin/teachers', [SuperadminDashboardController::class, 'teachers'])->name('admin.teachers');
    Route::get('/admin/teachers/create', [SuperadminDashboardController::class, 'createTeacher'])->name('admin.teachers.create');
    Route::post('/admin/teachers', [SuperadminDashboardController::class, 'storeTeacher'])->name('admin.teachers.store');
    Route::get('/admin/exams', [SuperadminDashboardController::class, 'exams'])->name('admin.exams');
    Route::get('/admin/exams/create', [SuperadminDashboardController::class, 'createExam'])->name('admin.exams.create');
    Route::post('/admin/exams', [SuperadminDashboardController::class, 'storeExam'])->name('admin.exams.store');
    Route::get('/teacher/dashboard', [TeacherExamSessionController::class, 'index'])->name('teacher.dashboard');
    Route::post('/teacher/exam-sessions', [TeacherExamSessionController::class, 'store'])->name('teacher.exam-sessions.store');
    Route::get('/teacher/exam-sessions/{examSession}', [TeacherExamSessionController::class, 'show'])->name('teacher.exam-sessions.show');
    Route::get('/teacher/exam-sessions/{examSession}/activity-logs', [TeacherExamSessionController::class, 'activityLogs'])->name('teacher.exam-sessions.activity-logs');
    Route::get('/teacher/exam-sessions/{examSession}/activity-stream', [TeacherExamSessionController::class, 'activityStream'])->name('teacher.exam-sessions.activity-stream');
    Route::post('/teacher/exam-sessions/{examSession}/entry-pin', [TeacherExamSessionController::class, 'generateEntryPin'])->name('teacher.exam-sessions.entry-pin');
    Route::post('/teacher/exam-sessions/{examSession}/exit-pin', [TeacherExamSessionController::class, 'generateExitPin'])->name('teacher.exam-sessions.exit-pin');
});
