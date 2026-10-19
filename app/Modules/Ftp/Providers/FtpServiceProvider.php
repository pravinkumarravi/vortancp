<?php

declare(strict_types=1);

namespace App\Modules\Ftp\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Ftp\FtpModule;
use App\Modules\Ftp\Services\FtpManagerService;
use Illuminate\Support\ServiceProvider;

class FtpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FtpManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(FtpModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'ftp');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: FTP Accounts
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_ftp', 'FTP Accounts', 'admin.ftp.index', 'arrow-path-rounded-square', 8)
                ->permission('ftp.manage')
                ->activeWhen('admin.ftp*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: FTP Accounts
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'files_storage',
            item: NavigationItem::make('customer_ftp', 'FTP Accounts', 'customer.ftp.index', 'arrow-path-rounded-square', 2)
                ->permission('ftp.manage')
                ->activeWhen('customer.ftp*'),
            sectionTitle: 'Files & Storage',
            sectionOrder: 20
        );
    }
}
