<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Ssl\Services\SslCertificateService;
use App\Modules\Ssl\Services\SslValidatorService;
use App\Modules\Ssl\SslModule;
use Illuminate\Support\ServiceProvider;

class SslServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SslValidatorService::class);
        $this->app->singleton(SslCertificateService::class);
        $this->app->singleton(\App\Modules\Ssl\Services\AutoSslFleetService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(SslModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'ssl');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Ssl\Commands\AutoSslRunCommand::class,
            ]);
        }

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: SSL Certificates
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('ssl_management', 'SSL/TLS Certificates', 'admin.ssl.index', 'lock-closed', 5)
                ->permission('ssl.manage')
                ->activeWhen('admin.ssl.index'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Admin Navigation: AutoSSL Fleet Manager
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('autossl_management', 'AutoSSL Fleet', 'admin.ssl.autossl', 'shield-check', 6)
                ->permission('ssl.manage')
                ->activeWhen('admin.ssl.autossl*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: SSL Certificates
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'security',
            item: NavigationItem::make('customer_ssl', 'SSL/TLS Certificates', 'customer.ssl.index', 'lock-closed', 1)
                ->permission('ssl.manage')
                ->activeWhen('customer.ssl*'),
            sectionTitle: 'Security & Access',
            sectionOrder: 20
        );
    }
}
