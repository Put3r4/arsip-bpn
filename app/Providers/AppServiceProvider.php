<?php

namespace App\Providers;

use App\Models\Section;
use App\Models\User;
use App\Policies\SectionPolicy;
use App\Policies\UserPolicy;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Section::class, SectionPolicy::class);

        Gate::define('super-admin', fn (User $user) => $user->isSuperAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isSuperAdmin());
        Gate::define('manage-sections', fn (User $user) => $user->isSuperAdmin());
        Gate::define('manage-archives', fn (User $user) => $user->isStaff());
    }
}
