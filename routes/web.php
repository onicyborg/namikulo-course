<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CourseMemberController;
use App\Http\Controllers\Operations\ScheduledJobController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\UserTimezoneController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('site.home');
})->name('site.home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:registration');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('/account/timezone', [UserTimezoneController::class, 'update'])
    ->middleware(['auth', 'active', 'throttle:10,1'])
    ->name('account.timezone.update');

Route::post('/operations/jobs/{task}', [ScheduledJobController::class, 'store'])
    ->middleware(['throttle:operations', 'cron.token'])->name('operations.jobs.store');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:'.UserRole::Admin->value])
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
        Route::resource('courses', CourseController::class)->except(['show', 'create', 'edit']);
        Route::post('courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
        Route::post('courses/{course}/unpublish', [CourseController::class, 'unpublish'])->name('courses.unpublish');
        Route::post('courses/{course}/archive', [CourseController::class, 'archive'])->name('courses.archive');
        Route::post('courses/{course}/featured', [CourseController::class, 'featured'])->name('courses.featured');
        Route::get('courses/{course}/curriculum', [SectionController::class, 'index'])->name('courses.curriculum');
        Route::post('courses/{course}/sections/reorder', [SectionController::class, 'reorder'])->name('courses.sections.reorder');
        Route::post('courses/{course}/sections', [SectionController::class, 'store'])->name('courses.sections.store');
        Route::put('courses/{course}/sections/{section}', [SectionController::class, 'update'])->name('courses.sections.update');
        Route::delete('courses/{course}/sections/{section}', [SectionController::class, 'destroy'])->name('courses.sections.destroy');
        Route::post('sections/{section}/lessons/reorder', [LessonController::class, 'reorder'])->name('sections.lessons.reorder');
        Route::post('sections/{section}/lessons', [LessonController::class, 'store'])->name('sections.lessons.store');
        Route::put('sections/{section}/lessons/{lesson}', [LessonController::class, 'update'])->name('sections.lessons.update');
        Route::delete('sections/{section}/lessons/{lesson}', [LessonController::class, 'destroy'])->name('sections.lessons.destroy');
        Route::get('users/search', [UserController::class, 'search'])->middleware('throttle:30,1')->name('users.search');
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('courses/{course}/members', [CourseMemberController::class, 'index'])->name('courses.members');
        Route::post('courses/{course}/members', [CourseMemberController::class, 'store'])->name('courses.members.store');
        Route::put('members/{enrollment}', [CourseMemberController::class, 'update'])->name('members.update');
        Route::post('members/{enrollment}/revoke', [CourseMemberController::class, 'revoke'])->name('members.revoke');
    });

Route::prefix('my')
    ->name('member.')
    ->middleware(['auth', 'active', 'role:'.UserRole::Member->value])
    ->group(function (): void {
        Route::get('/dashboard', MemberDashboardController::class)->name('dashboard');
    });
