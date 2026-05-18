<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperadminDashboardController;
use App\Http\Controllers\TeacherExamSessionController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('teacher.dashboard')
        : view('auth.login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/superadmin/dashboard', [SuperadminDashboardController::class, 'index'])->name('superadmin.dashboard');
    Route::get('/teacher/dashboard', [TeacherExamSessionController::class, 'index'])->name('teacher.dashboard');
    Route::post('/teacher/exam-sessions', [TeacherExamSessionController::class, 'store'])->name('teacher.exam-sessions.store');
    Route::get('/teacher/exam-sessions/{examSession}', [TeacherExamSessionController::class, 'show'])->name('teacher.exam-sessions.show');
    Route::post('/teacher/exam-sessions/{examSession}/entry-pin', [TeacherExamSessionController::class, 'generateEntryPin'])->name('teacher.exam-sessions.entry-pin');
    Route::post('/teacher/exam-sessions/{examSession}/exit-pin', [TeacherExamSessionController::class, 'generateExitPin'])->name('teacher.exam-sessions.exit-pin');
});
