<?php

namespace App\Providers;

use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\StorageRack;
use App\Models\User;
use App\Policies\ArchiveDocumentPolicy;
use App\Policies\DocumentLoanPolicy;
use App\Policies\DocumentTypePolicy;
use App\Policies\LandRightTypePolicy;
use App\Policies\SectionPolicy;
use App\Policies\ServiceActivityPolicy;
use App\Policies\StorageRackPolicy;
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
        Gate::policy(StorageRack::class, StorageRackPolicy::class);
        Gate::policy(DocumentType::class, DocumentTypePolicy::class);
        Gate::policy(LandRightType::class, LandRightTypePolicy::class);
        Gate::policy(ServiceActivity::class, ServiceActivityPolicy::class);
        Gate::policy(ArchiveDocument::class, ArchiveDocumentPolicy::class);
        Gate::policy(DocumentLoan::class, DocumentLoanPolicy::class);

        Gate::define('super-admin', fn (User $user) => $user->isSuperAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isSuperAdmin());
        Gate::define('manage-sections', fn (User $user) => $user->isSuperAdmin());
        Gate::define('manage-archives', fn (User $user) => $user->canManageArchives());
        Gate::define('manage-loans', fn (User $user) => $user->canManageArchives());
    }
}
