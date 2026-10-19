<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Metrics\MetricsModule;
use App\Modules\Metrics\Services\AccountMetricsAggregatorService;
use App\Modules\Metrics\Services\SystemMetricsCollectorService;
use Illuminate\Support\ServiceProvider;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SystemMetricsCollectorService::class);
        $this->app->singleton(AccountMetricsAggregatorService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(MetricsModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'metrics');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Metrics
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'server_management',
            item: NavigationItem::make('admin_metrics', 'System Metrics', 'admin.metrics.index', 'chart-bar', 15)
                ->permission('servers.manage')
                ->activeWhen('admin.metrics*'),
            sectionTitle: 'Infrastructure',
            sectionOrder: 20
        );

        // Customer Navigation: Resource Usage
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'account_settings',
            item: NavigationItem::make('customer_metrics', 'Resource Usage', 'customer.metrics.index', 'chart-bar', 2)
                ->permission('domains.view')
                ->activeWhen('customer.metrics*'),
            sectionTitle: 'Account & Settings',
            sectionOrder: 50
        );
    }
}
