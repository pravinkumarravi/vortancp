<?php

declare(strict_types=1);

namespace App\Modules\Backups\Providers;

use App\Modules\Backups\BackupModule;
use App\Modules\Backups\Services\BackupArchiveService;
use App\Modules\Backups\Services\BackupManagerService;
use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BackupArchiveService::class);
        $this->app->singleton(BackupManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(BackupModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'backups');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Backups
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_operations',
            item: NavigationItem::make('admin_backups', 'Backups & Snapshots', 'admin.backups.index', 'arrow-down-tray', 10)
                ->permission('backups.manage')
                ->activeWhen('admin.backups*'),
            sectionTitle: 'System Operations',
            sectionOrder: 40
        );

        // Customer Navigation: Backups
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'files_storage',
            item: NavigationItem::make('customer_backups', 'Backup & Restore', 'customer.backups.index', 'arrow-down-tray', 3)
                ->permission('backups.manage')
                ->activeWhen('customer.backups*'),
            sectionTitle: 'Files & Storage',
            sectionOrder: 20
        );
    }
}
