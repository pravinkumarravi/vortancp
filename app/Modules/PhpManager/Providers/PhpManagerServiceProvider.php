<?php

declare(strict_types=1);

namespace App\Modules\PhpManager\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\PhpManager\PhpManagerModule;
use App\Modules\PhpManager\Services\PhpFpmPoolCompilerService;
use App\Modules\PhpManager\Services\PhpManagerService;
use Illuminate\Support\ServiceProvider;

class PhpManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PhpFpmPoolCompilerService::class);
        $this->app->singleton(PhpManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(PhpManagerModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'phpmanager');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: PHP Manager
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_services',
            item: NavigationItem::make('admin_php', 'PHP Multi-Version', 'admin.php.index', 'cpu-chip', 9)
                ->permission('servers.manage')
                ->activeWhen('admin.php*'),
            sectionTitle: 'System & Services',
            sectionOrder: 40
        );

        // Customer Navigation: PHP Settings
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'web_hosting',
            item: NavigationItem::make('customer_php', 'PHP Settings', 'customer.php.index', 'cpu-chip', 3)
                ->permission('domains.view')
                ->activeWhen('customer.php*'),
            sectionTitle: 'Web Services',
            sectionOrder: 10
        );
    }
}
