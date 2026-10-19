<?php

declare(strict_types=1);

namespace App\Modules\Firewall\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Firewall\FirewallModule;
use App\Modules\Firewall\Services\FirewallManagerService;
use App\Modules\Firewall\Services\FirewallRuleCompilerService;
use Illuminate\Support\ServiceProvider;

class FirewallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirewallRuleCompilerService::class);
        $this->app->singleton(FirewallManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(FirewallModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'firewall');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Firewall & Security
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'security_firewall',
            item: NavigationItem::make('admin_firewall', 'Firewall & Ports', 'admin.firewall.index', 'shield-check', 1)
                ->permission('firewall.manage')
                ->activeWhen('admin.firewall*'),
            sectionTitle: 'Security & Protection',
            sectionOrder: 35
        );
    }
}
