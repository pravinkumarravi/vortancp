<?php

declare(strict_types=1);

namespace App\Modules\ServiceManager\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\ServiceManager\Commands\ServicesWatchdogCommand;
use App\Modules\ServiceManager\ServiceManagerModule;
use App\Modules\ServiceManager\Services\ServiceManagerService;
use Illuminate\Support\ServiceProvider;

class ServiceManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ServiceManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(ServiceManagerModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'service_manager');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ServicesWatchdogCommand::class,
            ]);
        }

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Service Manager & Daemons
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_settings',
            item: NavigationItem::make('admin_services', 'Service Manager', 'admin.services.index', 'cpu-chip', 40)
                ->permission('servers.manage')
                ->activeWhen('admin.services*'),
            sectionTitle: 'System Settings',
            sectionOrder: 90
        );
    }
}
