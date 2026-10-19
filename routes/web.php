<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CourseMemberController;
use App\Http\Controllers\Admin\VideoUploadController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OperationController;
use App\Http\Controllers\Operations\ScheduledJobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Member\LessonDiscussionController;
use App\Http\Controllers\Admin\DiscussionController;
use App\Http\Controllers\EditorImageController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\UserTimezoneController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\OrderController as MemberOrderController;
use App\Http\Controllers\Member\CourseController as MemberCourseController;
use App\Http\Controllers\Member\ProfileController as MemberProfileController;
use App\Http\Controllers\Member\LearnController;
use App\Http\Controllers\Stream\HlsStreamController;
use App\Http\Controllers\Site\CoursePageController;
use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Checkout\OrderPaymentController;
use App\Http\Controllers\Webhook\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CoursePageController::class, 'home'])->name('site.home');
Route::get('/courses', [CoursePageController::class, 'index'])->name('site.courses.index');
Route::get('/courses/{course:slug}', [CoursePageController::class, 'show'])->name('site.courses.show');
Route::get('/editor/images/{image}', [EditorImageController::class, 'show'])
    ->whereUuid('image')->name('editor.images.show');

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
    ->middleware(['auth', 'active', 'throttle:10,1,timezone:'])
    ->name('account.timezone.update');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/editor/images', [EditorImageController::class, 'store'])
        ->middleware('throttle:10,1,editor-image:')->name('editor.images.store');
    Route::get('/learn/{lesson}', [LearnController::class, 'show'])->name('learn.show');
    Route::get('/learn/{lesson}/discussion', [LearnController::class, 'show'])->name('learn.discussion.index');
    Route::get('/learn/{lesson}/discussion/{post}', [LessonDiscussionController::class, 'thread'])->whereUuid('post')->name('learn.discussion.thread');
    Route::post('/learn/{lesson}/discussion', [LessonDiscussionController::class, 'store'])
        ->middleware('throttle:5,1,discussion-post:')->name('learn.discussion.store');
    Route::delete('/learn/{lesson}/discussion/{post}', [LessonDiscussionController::class, 'destroy'])
        ->whereUuid('post')->middleware('throttle:10,1,discussion-delete:')->name('learn.discussion.destroy');
    Route::post('/learn/{lesson}/playback', [LearnController::class, 'playback'])->middleware('throttle:60,1,playback:')->name('learn.playback');
    Route::post('/learn/heartbeat/{token}', [LearnController::class, 'heartbeat'])->middleware('throttle:90,1,heartbeat:')->name('learn.heartbeat');
    Route::prefix('stream/{token}')->name('stream.')->middleware('throttle:600,1,stream:')->group(function (): void {
        Route::get('master.m3u8', [HlsStreamController::class, 'master'])->name('master');
        Route::get('source/index.m3u8', [HlsStreamController::class, 'playlist'])->name('playlist');
        Route::get('key.bin', [HlsStreamController::class, 'key'])->name('key');
        Route::get('source/{name}', [HlsStreamController::class, 'segment'])->where('name', 'seg_\d{5}\.ts')->name('segment');
    });
});

Route::post('/learn/lessons/{lesson}/progress', [LearnController::class, 'progress'])
    ->middleware(['auth', 'active', 'role:'.UserRole::Member->value, 'throttle:120,1,progress:'])->name('learn.progress');

Route::post('/operations/jobs/{task}', [ScheduledJobController::class, 'store'])
    ->middleware(['throttle:operations', 'cron.token'])->name('operations.jobs.store');

Route::post('/webhooks/midtrans', MidtransWebhookController::class)
    ->middleware('throttle:60,1,webhook:')->name('webhooks.midtrans');

Route::middleware(['auth', 'active', 'role:'.UserRole::Member->value])->group(function (): void {
    Route::get('/checkout/{course:slug}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{course:slug}', [CheckoutController::class, 'store'])->middleware('throttle:10,1,checkout:')->name('checkout.store');
    Route::get('/orders/{order}/pay', [OrderPaymentController::class, 'show'])->name('orders.pay');
    Route::get('/orders/{order}/status', [OrderPaymentController::class, 'status'])->middleware('throttle:30,1,order-status:')->name('orders.status');
    Route::post('/orders/{order}/cancel', [OrderPaymentController::class, 'cancel'])->middleware('throttle:10,1,order-cancel:')->name('orders.cancel');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:'.UserRole::Admin->value])
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('dashboard/orders/data', [AdminDashboardController::class, 'data'])->name('dashboard.orders.data');
        Route::get('notifications/data', [NotificationController::class, 'data'])->name('notifications.data');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');
        Route::get('discussions/data', [DiscussionController::class, 'data'])->name('discussions.data');
        Route::get('discussions', [DiscussionController::class, 'index'])->name('discussions.index');
        Route::post('discussions/{post}/hide', [DiscussionController::class, 'hide'])->whereUuid('post')->name('discussions.hide');
        Route::post('discussions/{post}/restore', [DiscussionController::class, 'restore'])->whereUuid('post')->name('discussions.restore');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::get('operations/data', [OperationController::class, 'data'])->name('operations.data');
        Route::get('operations', [OperationController::class, 'index'])->name('operations.index');
        Route::get('courses/{course}/preview', [CoursePageController::class, 'preview'])->name('courses.preview');
        Route::get('lessons/{lesson}/video/prepare', [VideoUploadController::class, 'prepare'])->name('lessons.video.prepare');
        Route::delete('lessons/{lesson}/video', [VideoUploadController::class, 'destroy'])->name('lessons.video.destroy');
        Route::post('lessons/{lesson}/video/uploads', [VideoUploadController::class, 'store'])->middleware('throttle:10,1,upload-start:')->name('lessons.video.uploads.store');
        Route::post('video-uploads/{upload}/manifest', [VideoUploadController::class, 'manifest'])->middleware('throttle:30,1,upload-manifest:')->name('video-uploads.manifest');
        Route::post('video-uploads/{upload}/chunks/{name}/{index}', [VideoUploadController::class, 'chunk'])->where('name', 'seg_\d{5}\.ts')->whereNumber('index')->middleware('throttle:120,1,upload-chunk:')->name('video-uploads.chunk');
        Route::post('video-uploads/{upload}/finalize', [VideoUploadController::class, 'finalize'])->middleware('throttle:30,1,upload-finalize:')->name('video-uploads.finalize');
        Route::get('video-uploads/{upload}/status', [VideoUploadController::class, 'status'])->middleware('throttle:30,1,upload-status:')->name('video-uploads.status');
        Route::post('video-uploads/{upload}/cancel', [VideoUploadController::class, 'cancel'])->middleware('throttle:30,1,upload-cancel:')->name('video-uploads.cancel');
        Route::get('categories/{category}/modal', [CategoryController::class, 'modal'])->whereUuid('category')->name('categories.modal');
        Route::get('courses/{course}/modal', [CourseController::class, 'modal'])->whereUuid('course')->name('courses.modal');
        Route::get('users/{user}/modal', [UserController::class, 'modal'])->whereUuid('user')->name('users.modal');
        Route::get('members/{enrollment}/modal', [CourseMemberController::class, 'modal'])->whereUuid('enrollment')->name('members.modal');
        Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
        Route::get('courses/data', [CourseController::class, 'data'])->name('courses.data');
        Route::get('users/data', [UserController::class, 'data'])->name('users.data');
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
        Route::get('users/search', [UserController::class, 'search'])->middleware('throttle:30,1,user-search:')->name('users.search');
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('courses/{course}/members/data', [CourseMemberController::class, 'data'])->name('courses.members.data');
        Route::get('courses/{course}/members', [CourseMemberController::class, 'index'])->name('courses.members');
        Route::get('orders/data', [AdminOrderController::class, 'data'])->name('orders.data');
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/sync', [AdminOrderController::class, 'sync'])->middleware('throttle:10,1,admin-sync:')->name('orders.sync');
        Route::post('orders/{order}/retry-email', [AdminOrderController::class, 'retryEmail'])->middleware('throttle:5,1,admin-email:')->name('orders.retry-email');
        Route::post('orders/{order}/payments/{payment}/retry-instructions-email', [AdminOrderController::class, 'retryInstructionsEmail'])->middleware('throttle:5,1,admin-email:')->name('orders.retry-instructions-email');
        Route::post('courses/{course}/members', [CourseMemberController::class, 'store'])->name('courses.members.store');
        Route::put('members/{enrollment}', [CourseMemberController::class, 'update'])->name('members.update');
        Route::post('members/{enrollment}/revoke', [CourseMemberController::class, 'revoke'])->name('members.revoke');
    });

Route::prefix('my')
    ->name('member.')
    ->middleware(['auth', 'active', 'role:'.UserRole::Member->value])
    ->group(function (): void {
        Route::get('/dashboard', MemberDashboardController::class)->name('dashboard');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');
        Route::get('/courses', [MemberCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course:slug}', [MemberCourseController::class, 'show'])->withTrashed()->name('courses.show');
        Route::get('/profile', [MemberProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [MemberProfileController::class, 'update'])->name('profile.update');
        Route::get('/orders/data', [MemberOrderController::class, 'data'])->name('orders.data');
        Route::get('/orders', [MemberOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}/invoice', [MemberOrderController::class, 'invoice'])->middleware('throttle:10,1,invoice:')->name('orders.invoice');
    });
