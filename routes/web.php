<?php

use App\Http\Controllers\Web\AtRiskController;
use App\Http\Controllers\Web\CohortsController;
use App\Http\Controllers\Web\CoursesWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EngagementController;
use App\Http\Controllers\Web\LanguagesController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\QuizzesController;
use App\Http\Controllers\Web\UsersWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('admin.session')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/engagement', [EngagementController::class, 'index'])->name('engagement');
    Route::get('/quizzes', [QuizzesController::class, 'index'])->name('quizzes');
    Route::get('/at-risk', [AtRiskController::class, 'index'])->name('atrisk');
    Route::get('/cohorts', [CohortsController::class, 'index'])->name('cohorts');
    Route::get('/languages', [LanguagesController::class, 'index'])->name('languages');

    Route::get('/users', [UsersWebController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UsersWebController::class, 'show'])->whereNumber('id')->name('users.show');

    Route::get('/courses', [CoursesWebController::class, 'index'])->name('courses.index');
    Route::get('/courses/{id}', [CoursesWebController::class, 'show'])->whereNumber('id')->name('courses.show');
});
