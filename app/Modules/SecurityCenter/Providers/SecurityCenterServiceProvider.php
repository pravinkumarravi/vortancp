<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\SecurityCenter\SecurityCenterModule;
use App\Modules\SecurityCenter\Services\ModSecurityCompilerService;
use App\Modules\SecurityCenter\Services\SecurityAdvisorService;
use App\Modules\SecurityCenter\Services\SecurityScannerService;
use Illuminate\Support\ServiceProvider;

class SecurityCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModSecurityCompilerService::class);
        $this->app->singleton(SecurityScannerService::class);
        $this->app->singleton(SecurityAdvisorService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(SecurityCenterModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'security-center');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Security Center
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'security_firewall',
            item: NavigationItem::make('admin_security_center', 'Security Center & WAF', 'admin.security.index', 'lock-closed', 2)
                ->permission('security.manage')
                ->activeWhen('admin.security*'),
            sectionTitle: 'Security & Protection',
            sectionOrder: 35
        );
    }
}
