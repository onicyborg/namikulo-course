<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Enrollment;
use App\Models\User;
use App\Policies\ContentPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\UserPolicy;
use App\Services\HlsVideoDelivery;
use App\Services\VideoDelivery;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VideoDelivery::class, HlsVideoDelivery::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.dashboard.topbar', function ($view): void {
            $user = auth()->user();
            $view->with('unreadNotifications', $user->userNotifications()->whereNull('read_at')->count())
                ->with('recentNotifications', $user->userNotifications()
                    ->latest('created_at')->limit(5)->get());
        });
        foreach ([Category::class, Course::class, Section::class, Lesson::class] as $model) {
            Gate::policy($model, ContentPolicy::class);
        }
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('operations', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
    }
}
