<?php

declare(strict_types=1);

namespace App\Modules\Cron\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Cron\CronModule;
use App\Modules\Cron\Services\CronManagerService;
use App\Modules\Cron\Services\CrontabCompilerService;
use Illuminate\Support\ServiceProvider;

class CronServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CrontabCompilerService::class);
        $this->app->singleton(CronManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(CronModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'cron');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Cron Jobs
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_cron', 'Cron Jobs', 'admin.cron.index', 'clock', 9)
                ->permission('cron.manage')
                ->activeWhen('admin.cron*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: Cron Jobs
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'advanced_tools',
            item: NavigationItem::make('customer_cron', 'Cron Jobs', 'customer.cron.index', 'clock', 1)
                ->permission('cron.manage')
                ->activeWhen('customer.cron*'),
            sectionTitle: 'Advanced Tools',
            sectionOrder: 35
        );
    }
}
