<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Identity\IdentityModule;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Services\TotpService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind TotpService
        $this->app->singleton(TotpService::class, function () {
            return new TotpService();
        });

        // Register IdentityModule with ModuleRegistry
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(IdentityModule::class);
    }

    public function boot(): void
    {
        // Define Gates for authorization
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
            return null;
        });

        Gate::after(function ($user, $ability) {
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability);
            }
            return false;
        });

        // Register default navigation items
        $navigation = $this->app->make(NavigationRegistry::class);

        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'access_control',
            item: NavigationItem::make('users', 'Users & Accounts', 'admin.users', 'users', 1)
                ->permission('users.view')
                ->activeWhen('admin.users*'),
            sectionTitle: 'Access Control',
            sectionOrder: 90
        );

        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'access_control',
            item: NavigationItem::make('roles', 'Roles & Permissions', 'admin.roles', 'shield', 2)
                ->role(Role::SUPER_ADMIN)
                ->activeWhen('admin.roles*'),
            sectionTitle: 'Access Control',
            sectionOrder: 90
        );
    }
}
