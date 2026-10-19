<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Providers;

use App\Modules\Accounts\AccountsModule;
use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(AccountsModule::class);
    }

    public function boot(): void
    {
        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Hosting Accounts
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('hosting_accounts', 'Hosting Accounts', 'admin.accounts.index', 'circle-stack', 1)
                ->permission('accounts.view')
                ->activeWhen('admin.accounts*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Admin Navigation: Customers
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('customers_list', 'Customers & Clients', 'admin.customers.index', 'user-group', 2)
                ->permission('accounts.view')
                ->activeWhen('admin.customers*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );
    }
}
