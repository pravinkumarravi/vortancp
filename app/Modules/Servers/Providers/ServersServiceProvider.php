<?php

declare(strict_types=1);

namespace App\Modules\Servers\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Servers\Contracts\ServerNodeAdapterInterface;
use App\Modules\Servers\Infrastructure\Adapters\LocalNodeAdapter;
use App\Modules\Servers\ServersModule;
use App\Modules\Servers\Services\ServerNodeService;
use Illuminate\Support\ServiceProvider;

class ServersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind node adapter interface
        $this->app->bind(ServerNodeAdapterInterface::class, function () {
            return new LocalNodeAdapter();
        });

        // Bind ServerNodeService
        $this->app->singleton(ServerNodeService::class, function ($app) {
            return new ServerNodeService(
                $app->make(ServerNodeAdapterInterface::class),
                $app->make(\App\Modules\Audit\Services\AuditLogger::class)
            );
        });

        // Register module in registry
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(ServersModule::class);
    }

    public function boot(): void
    {
        $navigation = $this->app->make(NavigationRegistry::class);

        // Register Server Nodes item under Admin navigation
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'infrastructure',
            item: NavigationItem::make('servers_list', 'Server Nodes', 'admin.servers.index', 'server', 1)
                ->permission('servers.view')
                ->activeWhen('admin.servers*'),
            sectionTitle: 'Infrastructure',
            sectionOrder: 20
        );
    }
}
