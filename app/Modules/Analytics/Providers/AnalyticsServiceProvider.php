<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Providers;

use App\Modules\Analytics\AnalyticsModule;
use App\Modules\Analytics\Livewire\DomainAnalytics;
use App\Modules\Analytics\Services\AccessLogParserService;
use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessLogParserService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(AnalyticsModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'analytics');

        Livewire::component('analytics.domain-analytics', DomainAnalytics::class);

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Traffic Analytics
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_settings',
            item: NavigationItem::make('admin_analytics', 'Traffic Analytics', 'admin.analytics.index', 'chart-bar', 52)
                ->permission('servers.manage')
                ->activeWhen('admin.analytics*'),
            sectionTitle: 'System Settings',
            sectionOrder: 90
        );

        // Customer Navigation: Visitor Analytics
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'advanced_tools',
            item: NavigationItem::make('customer_analytics', 'Visitor Analytics', 'customer.analytics.index', 'chart-bar', 18)
                ->permission('domains.view')
                ->activeWhen('customer.analytics*'),
            sectionTitle: 'Advanced Tools',
            sectionOrder: 40
        );
    }
}
