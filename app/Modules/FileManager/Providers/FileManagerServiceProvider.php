<?php

declare(strict_types=1);

namespace App\Modules\FileManager\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\FileManager\FileManagerModule;
use App\Modules\FileManager\Services\FilesystemJailService;
use Illuminate\Support\ServiceProvider;

class FileManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FilesystemJailService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(FileManagerModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'filemanager');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: File Manager
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_files', 'File Manager', 'admin.files.index', 'folder', 6)
                ->permission('files.manage')
                ->activeWhen('admin.files*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: File Manager
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'files_storage',
            item: NavigationItem::make('customer_files', 'File Manager', 'customer.files.index', 'folder', 1)
                ->permission('files.manage')
                ->activeWhen('customer.files*'),
            sectionTitle: 'Files & Storage',
            sectionOrder: 15
        );
    }
}
