<?php

declare(strict_types=1);

namespace App\Modules\Logs\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Logs\LogsModule;
use App\Modules\Logs\Services\LogReaderService;
use Illuminate\Support\ServiceProvider;

class LogsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LogReaderService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(LogsModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'logs');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: System Logs
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_settings',
            item: NavigationItem::make('admin_logs', 'System Logs', 'admin.logs.index', 'document-magnifying-glass', 50)
                ->permission('servers.manage')
                ->activeWhen('admin.logs*'),
            sectionTitle: 'System Settings',
            sectionOrder: 90
        );

        // Customer Navigation: Domain Logs
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'advanced_tools',
            item: NavigationItem::make('customer_logs', 'Domain Logs', 'customer.logs.index', 'document-text', 5)
                ->permission('domains.view')
                ->activeWhen('customer.logs*'),
            sectionTitle: 'Advanced Tools',
            sectionOrder: 40
        );
    }
}
