<?php

declare(strict_types=1);

namespace App\Modules\Databases\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Databases\DatabasesModule;
use App\Modules\Databases\Services\DatabaseManagerService;
use Illuminate\Support\ServiceProvider;

class DatabasesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(DatabasesModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'databases');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Databases
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_databases', 'Databases & Users', 'admin.databases.index', 'circle-stack', 7)
                ->permission('databases.manage')
                ->activeWhen('admin.databases*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: Databases
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'database_services',
            item: NavigationItem::make('customer_databases', 'Databases & Users', 'customer.databases.index', 'circle-stack', 1)
                ->permission('databases.manage')
                ->activeWhen('customer.databases*'),
            sectionTitle: 'Databases',
            sectionOrder: 25
        );
    }
}
