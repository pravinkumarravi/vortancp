<?php

declare(strict_types=1);

namespace App\Modules\Agent\Providers;

use App\Modules\Agent\AgentModule;
use App\Modules\Agent\Console\Commands\VortanAgentDaemonCommand;
use App\Modules\Agent\Services\AgentClientService;
use App\Modules\Agent\Services\AgentDispatcherService;
use App\Modules\Agent\Services\AgentProtocolService;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentProtocolService::class);
        $this->app->singleton(AgentDispatcherService::class);
        $this->app->singleton(AgentClientService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(AgentModule::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                VortanAgentDaemonCommand::class,
            ]);
        }
    }
}
