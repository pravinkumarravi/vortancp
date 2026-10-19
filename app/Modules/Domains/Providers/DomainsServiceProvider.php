<?php

declare(strict_types=1);

namespace App\Modules\Domains\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Domains\DomainsModule;
use App\Modules\Domains\Services\DomainProvisioningService;
use App\Modules\Domains\Services\VirtualHostCompilerService;
use Illuminate\Support\ServiceProvider;

class DomainsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VirtualHostCompilerService::class);
        $this->app->singleton(DomainProvisioningService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(DomainsModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'domains');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Domains & Vhosts
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('domains_management', 'Domains & Websites', 'admin.domains.index', 'globe-alt', 3)
                ->permission('domains.view')
                ->activeWhen('admin.domains*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: Websites & Domains
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'web_hosting',
            item: NavigationItem::make('customer_domains', 'Websites & Domains', 'customer.domains.index', 'globe-alt', 1)
                ->permission('domains.view')
                ->activeWhen('customer.domains*'),
            sectionTitle: 'Web Services',
            sectionOrder: 10
        );
    }
}
