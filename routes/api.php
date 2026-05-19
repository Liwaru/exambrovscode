<?php

use App\Http\Controllers\Api\ExternalExamController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\StudentAuthController;
use App\Http\Controllers\Api\StudentExamSessionController;
use Illuminate\Support\Facades\Route;

Route::post('/external/exams/sync', [ExternalExamController::class, 'sync']);
Route::get('/external/incidents', [IncidentController::class, 'index']);

Route::post('/student/login', [StudentAuthController::class, 'login']);
Route::post('/student/exam-sessions/join', [StudentExamSessionController::class, 'join']);
Route::post('/student/exam-sessions/{examSession}/heartbeat', [StudentExamSessionController::class, 'heartbeat']);
Route::post('/student/exam-sessions/{examSession}/exit', [StudentExamSessionController::class, 'exit']);
Route::post('/student/exam-sessions/{examSession}/interrupt', [StudentExamSessionController::class, 'markInterrupted']);
Route::post('/student/exam-sessions/{examSession}/activity', [StudentExamSessionController::class, 'activity']);
