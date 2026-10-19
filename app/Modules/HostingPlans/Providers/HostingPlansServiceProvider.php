<?php

declare(strict_types=1);

namespace App\Modules\HostingPlans\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\HostingPlans\HostingPlansModule;
use App\Modules\HostingPlans\Services\EntitlementService;
use Illuminate\Support\ServiceProvider;

class HostingPlansServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EntitlementService::class, function () {
            return new EntitlementService();
        });

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(HostingPlansModule::class);
    }

    public function boot(): void
    {
        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Hosting Packages
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('hosting_plans_list', 'Hosting Packages', 'admin.plans.index', 'squares-plus', 4)
                ->permission('accounts.manage')
                ->activeWhen('admin.plans*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Reseller Navigation: Packages
        $navigation->registerItem(
            panel: 'reseller',
            sectionId: 'client_services',
            item: NavigationItem::make('reseller_plans_list', 'Service Packages', 'reseller.plans.index', 'squares-plus', 2)
                ->activeWhen('reseller.plans*'),
            sectionTitle: 'Client Services',
            sectionOrder: 10
        );
    }
}
