<?php

namespace App\Providers;

use App\Enums\Role;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        // Root has full access; policies still run for fine-grained messages.
        Gate::before(function ($user, string $ability) {
            if ($user?->role === Role::Root) {
                return true;
            }
            return null;
        });
    }
}
