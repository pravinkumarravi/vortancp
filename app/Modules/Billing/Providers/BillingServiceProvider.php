<?php

declare(strict_types=1);

namespace App\Modules\Billing\Providers;

use App\Modules\Billing\BillingModule;
use App\Modules\Billing\Services\BillingProvisioningService;
use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BillingProvisioningService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(BillingModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'billing');

        // Load API routes
        Route::prefix('api/v1/billing')
            ->middleware(['api'])
            ->group(__DIR__ . '/../Routes/api.php');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: API Keys & Billing Integration
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_settings',
            item: NavigationItem::make('admin_billing', 'API & Billing', 'admin.billing.index', 'key', 35)
                ->permission('servers.manage')
                ->activeWhen('admin.billing*'),
            sectionTitle: 'System Settings',
            sectionOrder: 90
        );
    }
}
