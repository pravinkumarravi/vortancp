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

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(SslModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'ssl');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: SSL Certificates
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('ssl_management', 'SSL/TLS Certificates', 'admin.ssl.index', 'lock-closed', 5)
                ->permission('ssl.manage')
                ->activeWhen('admin.ssl*'),
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
