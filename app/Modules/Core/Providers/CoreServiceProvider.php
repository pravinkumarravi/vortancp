<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use App\Modules\Core\CoreModule;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind ModuleRegistry as singleton
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry($app);
        });

        // Bind NavigationRegistry as singleton
        $this->app->singleton(NavigationRegistry::class, function () {
            return new NavigationRegistry();
        });

        // Register CoreModule
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(CoreModule::class);
    }

    public function boot(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->bootEnabledModules();

        // View composer for active panel navigation
        View::composer('*', function ($view) {
            $user = auth()->user();
            $navigation = $this->app->make(NavigationRegistry::class);
            $view->with('navRegistry', $navigation);
        });
    }
}
