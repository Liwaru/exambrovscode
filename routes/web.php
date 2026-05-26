<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDatabaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperadminDashboardController;
use App\Http\Controllers\TeacherExamSessionController;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('auth.login');
    }

    return auth()->user()->isAdmin() || auth()->user()->isHeadmaster()
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
    Route::get('/admin/activity-logs', [SuperadminDashboardController::class, 'activityLogs'])->name('admin.activity-logs');
    Route::post('/admin/activity-logs/{activityLog}/restore', [SuperadminDashboardController::class, 'restoreActivity'])->name('admin.activity-logs.restore');
    Route::get('/admin/database', [AdminDatabaseController::class, 'index'])->name('admin.database');
    Route::post('/admin/database/backup', [AdminDatabaseController::class, 'backup'])->name('admin.database.backup');
    Route::get('/admin/database/backups/{filename}', [AdminDatabaseController::class, 'download'])->name('admin.database.download');
    Route::post('/admin/database/import', [AdminDatabaseController::class, 'import'])->name('admin.database.import');
    Route::post('/admin/database/reset', [AdminDatabaseController::class, 'reset'])->name('admin.database.reset');
    Route::get('/admin/students', [SuperadminDashboardController::class, 'students'])->name('admin.students');
    Route::get('/admin/students/create', [SuperadminDashboardController::class, 'createStudent'])->name('admin.students.create');
    Route::post('/admin/students', [SuperadminDashboardController::class, 'storeStudent'])->name('admin.students.store');
    Route::get('/admin/students/{student}/edit', [SuperadminDashboardController::class, 'editStudent'])->name('admin.students.edit');
    Route::put('/admin/students/{student}', [SuperadminDashboardController::class, 'updateStudent'])->name('admin.students.update');
    Route::delete('/admin/students/{student}', [SuperadminDashboardController::class, 'destroyStudent'])->name('admin.students.destroy');
    Route::get('/admin/teachers', [SuperadminDashboardController::class, 'teachers'])->name('admin.teachers');
    Route::get('/admin/teachers/create', [SuperadminDashboardController::class, 'createTeacher'])->name('admin.teachers.create');
    Route::post('/admin/teachers', [SuperadminDashboardController::class, 'storeTeacher'])->name('admin.teachers.store');
    Route::get('/admin/teachers/{teacher}/edit', [SuperadminDashboardController::class, 'editTeacher'])->name('admin.teachers.edit');
    Route::put('/admin/teachers/{teacher}', [SuperadminDashboardController::class, 'updateTeacher'])->name('admin.teachers.update');
    Route::delete('/admin/teachers/{teacher}', [SuperadminDashboardController::class, 'destroyTeacher'])->name('admin.teachers.destroy');
    Route::get('/admin/exams', [SuperadminDashboardController::class, 'exams'])->name('admin.exams');
    Route::get('/admin/exams/create', [SuperadminDashboardController::class, 'createExam'])->name('admin.exams.create');
    Route::post('/admin/exams', [SuperadminDashboardController::class, 'storeExam'])->name('admin.exams.store');
    Route::get('/admin/exams/{exam}/edit', [SuperadminDashboardController::class, 'editExam'])->name('admin.exams.edit');
    Route::put('/admin/exams/{exam}', [SuperadminDashboardController::class, 'updateExam'])->name('admin.exams.update');
    Route::delete('/admin/exams/{exam}', [SuperadminDashboardController::class, 'destroyExam'])->name('admin.exams.destroy');
    Route::get('/teacher/dashboard', [TeacherExamSessionController::class, 'index'])->name('teacher.dashboard');
    Route::post('/teacher/exam-sessions', [TeacherExamSessionController::class, 'store'])->name('teacher.exam-sessions.store');
    Route::delete('/teacher/exam-sessions', [TeacherExamSessionController::class, 'destroySelected'])->name('teacher.exam-sessions.destroy-selected');
    Route::get('/teacher/exam-sessions/{examSession}', [TeacherExamSessionController::class, 'show'])->name('teacher.exam-sessions.show');
    Route::get('/teacher/exam-sessions/{examSession}/activity-logs', [TeacherExamSessionController::class, 'activityLogs'])->name('teacher.exam-sessions.activity-logs');
    Route::post('/teacher/exam-sessions/{examSession}/status', [TeacherExamSessionController::class, 'updateStatus'])->name('teacher.exam-sessions.status');
    Route::post('/teacher/exam-sessions/{examSession}/entry-pin', [TeacherExamSessionController::class, 'generateEntryPin'])->name('teacher.exam-sessions.entry-pin');
    Route::post('/teacher/exam-sessions/{examSession}/exit-pin', [TeacherExamSessionController::class, 'generateExitPin'])->name('teacher.exam-sessions.exit-pin');
});
