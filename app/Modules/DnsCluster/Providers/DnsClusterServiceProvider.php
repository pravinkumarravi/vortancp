<?php

declare(strict_types=1);

namespace App\Modules\DnsCluster\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\DnsCluster\DnsClusterModule;
use App\Modules\DnsCluster\Livewire\DnsClusterManager;
use App\Modules\DnsCluster\Services\DnsClusterSyncService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DnsClusterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DnsClusterSyncService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(DnsClusterModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'dns_cluster');

        Livewire::component('dns_cluster.dns-cluster-manager', DnsClusterManager::class);

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: DNS Cluster
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_dns_cluster', 'DNS Cluster', 'admin.dns_cluster.index', 'globe-alt', 22)
                ->permission('dns.manage')
                ->activeWhen('admin.dns_cluster*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );
    }
}
