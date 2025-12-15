<?php

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
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('auth', fn (User $user) => $user !== null);
        Gate::define('admin', fn (User $user) => $user->role === 'admin');
        Gate::define('leader', fn (User $user) => $user->role === 'leader');
        Gate::define('employee', fn (User $user) => $user->role === 'employee');
    }
}
