<?php

declare(strict_types=1);

namespace App\Modules\AppInstaller\Providers;

use App\Modules\AppInstaller\AppInstallerModule;
use App\Modules\AppInstaller\Services\AppCatalogService;
use App\Modules\AppInstaller\Services\AppInstallerService;
use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class AppInstallerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppCatalogService::class);
        $this->app->singleton(AppInstallerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(AppInstallerModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'app-installer');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Application Installer
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_app_installer', 'Application Installer', 'admin.apps.index', 'sparkles', 16)
                ->permission('apps.manage')
                ->activeWhen('admin.apps*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: Application Installer
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'applications',
            item: NavigationItem::make('customer_app_installer', 'App Installer', 'customer.apps.index', 'sparkles', 2)
                ->permission('apps.manage')
                ->activeWhen('customer.apps*'),
            sectionTitle: 'Applications',
            sectionOrder: 35
        );
    }
}
