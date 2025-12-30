<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('auth', static fn(User $user) => $user !== null);
        Gate::define('admin', static fn(User $user) => $user->role === 'admin');
        Gate::define('leader', static fn(User $user) => $user->role === 'leader');
        Gate::define('employee', static fn(User $user) => $user->role === 'employee');
    }
}
