<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CohortController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show'])->whereNumber('id');
    Route::get('/users/{id}/courses', [UserController::class, 'courses'])->whereNumber('id');
    Route::get('/users/{id}/grades', [UserController::class, 'grades'])->whereNumber('id');
    Route::get('/users/{id}/quiz-attempts', [UserController::class, 'quizAttempts'])->whereNumber('id');

    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show'])->whereNumber('id');
    Route::get('/courses/{id}/students', [CourseController::class, 'students'])->whereNumber('id');
    Route::get('/courses/{id}/activities', [CourseController::class, 'activities'])->whereNumber('id');
    Route::get('/courses/{id}/grades', [CourseController::class, 'grades'])->whereNumber('id');

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->whereNumber('id');

    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::get('/quizzes/{id}', [QuizController::class, 'show'])->whereNumber('id');
    Route::get('/quizzes/{id}/attempts', [QuizController::class, 'attempts'])->whereNumber('id');

    Route::get('/cohorts', [CohortController::class, 'index']);
    Route::get('/cohorts/{id}', [CohortController::class, 'show'])->whereNumber('id');
    Route::get('/cohorts/{id}/members', [CohortController::class, 'members'])->whereNumber('id');

    Route::get('/stats/overview', [StatsController::class, 'overview']);
    Route::get('/stats/courses/{id}', [StatsController::class, 'course'])->whereNumber('id');
    Route::get('/stats/users/{id}/activity', [StatsController::class, 'userActivity'])->whereNumber('id');
});
