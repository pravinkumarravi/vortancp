<?php

declare(strict_types=1);

namespace App\Modules\Dns\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Dns\DnsModule;
use App\Modules\Dns\Services\DnsZoneCompilerService;
use App\Modules\Dns\Services\DnsZoneService;
use Illuminate\Support\ServiceProvider;

class DnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DnsZoneCompilerService::class);
        $this->app->singleton(DnsZoneService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(DnsModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'dns');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: DNS Zones
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('dns_zones', 'DNS Zone Editor', 'admin.dns.index', 'server', 4)
                ->permission('dns.manage')
                ->activeWhen('admin.dns*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: DNS Zones
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'web_hosting',
            item: NavigationItem::make('customer_dns', 'DNS Zone Editor', 'customer.dns.index', 'server', 2)
                ->permission('dns.manage')
                ->activeWhen('customer.dns*'),
            sectionTitle: 'Web Services',
            sectionOrder: 10
        );
    }
}
