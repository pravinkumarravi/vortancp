<?php

declare(strict_types=1);

namespace App\Modules\Terminal\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Terminal\Livewire\WebTerminal;
use App\Modules\Terminal\Services\TerminalExecutionService;
use App\Modules\Terminal\TerminalModule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TerminalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TerminalExecutionService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(TerminalModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'terminal');

        Livewire::component('terminal.web-terminal', WebTerminal::class);

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Web Terminal
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_settings',
            item: NavigationItem::make('admin_terminal', 'Web Terminal', 'admin.terminal.index', 'command-line', 55)
                ->permission('servers.manage')
                ->activeWhen('admin.terminal*'),
            sectionTitle: 'System Settings',
            sectionOrder: 90
        );

        // Customer Navigation: Web Terminal
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'advanced_tools',
            item: NavigationItem::make('customer_terminal', 'Web Terminal', 'customer.terminal.index', 'command-line', 12)
                ->permission('domains.view')
                ->activeWhen('customer.terminal*'),
            sectionTitle: 'Advanced Tools',
            sectionOrder: 40
        );
    }
}
