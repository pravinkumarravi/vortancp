<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Dashboard\DashboardModule;
use App\Modules\Identity\Models\Role;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(DashboardModule::class);
    }

    public function boot(): void
    {
        $navigation = $this->app->make(NavigationRegistry::class);

        // 1. Admin Dashboard Navigation
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'main',
            item: NavigationItem::make('admin_overview', 'Cluster Overview', 'admin.dashboard', 'chart-bar', 1)
                ->role(Role::SERVER_ADMIN)
                ->activeWhen('admin.dashboard*'),
            sectionTitle: 'Dashboard',
            sectionOrder: 1
        );

        // 2. Reseller Dashboard Navigation
        $navigation->registerItem(
            panel: 'reseller',
            sectionId: 'main',
            item: NavigationItem::make('reseller_overview', 'Resource Hub', 'reseller.dashboard', 'server-stack', 1)
                ->role(Role::RESELLER)
                ->activeWhen('reseller.dashboard*'),
            sectionTitle: 'Dashboard',
            sectionOrder: 1
        );

        // 3. Customer Dashboard Navigation
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'main',
            item: NavigationItem::make('customer_overview', 'Hosting Overview', 'customer.dashboard', 'home', 1)
                ->role(Role::CUSTOMER)
                ->activeWhen('customer.dashboard*'),
            sectionTitle: 'Dashboard',
            sectionOrder: 1
        );
    }
}
