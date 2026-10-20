<?php

declare(strict_types=1);

namespace App\Modules\Migration\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Migration\Livewire\MigrationManager;
use App\Modules\Migration\MigrationModule;
use App\Modules\Migration\Services\CpanelArchiveParserService;
use App\Modules\Migration\Services\MigrationExecutionService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CpanelArchiveParserService::class);
        $this->app->singleton(MigrationExecutionService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(MigrationModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'migration');

        Livewire::component('migration.migration-manager', MigrationManager::class);

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Server Migration
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_settings',
            item: NavigationItem::make('admin_migration', 'Server Migration', 'admin.migration.index', 'arrow-path-rounded-square', 48)
                ->permission('servers.manage')
                ->activeWhen('admin.migration*'),
            sectionTitle: 'System Settings',
            sectionOrder: 90
        );
    }
}
