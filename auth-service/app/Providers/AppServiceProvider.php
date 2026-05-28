<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Policies\AdminPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\UserRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\SessionRepositoryInterface::class,
            \App\Repositories\Eloquent\SessionRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\RoleRepositoryInterface::class,
            \App\Repositories\Eloquent\RoleRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\DepartmentRepositoryInterface::class,
            \App\Repositories\Eloquent\DepartmentRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\PermissionRepositoryInterface::class,
            \App\Repositories\Eloquent\PermissionRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\AuditLogRepositoryInterface::class,
            \App\Repositories\Eloquent\AuditLogRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() . '|' . $request->input('email'));
        });

        Gate::define('manage-users', [AdminPolicy::class, 'manageUsers']);
        Gate::define('manage-roles', [\App\Policies\RolePolicy::class, 'manage']);
        Gate::define('manage-departments', [\App\Policies\DepartmentPolicy::class, 'manage']);
    }
}
