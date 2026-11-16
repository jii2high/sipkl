<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\PrivateFilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('download-private-file', function (User $user, string $path): bool {
            return PrivateFilePolicy::allows($user, $path);
        });
    }
}
