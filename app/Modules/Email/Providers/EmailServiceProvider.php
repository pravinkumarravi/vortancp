<?php

declare(strict_types=1);

namespace App\Modules\Email\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Email\EmailModule;
use App\Modules\Email\Services\MailboxManagerService;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailboxManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(EmailModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'email');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Mailboxes
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_email', 'Email & Mailboxes', 'admin.email.index', 'envelope', 8)
                ->permission('email.manage')
                ->activeWhen('admin.email*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: Mailboxes
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'communication',
            item: NavigationItem::make('customer_email', 'Email Accounts', 'customer.email.index', 'envelope', 1)
                ->permission('email.manage')
                ->activeWhen('customer.email*'),
            sectionTitle: 'Email & Mailboxes',
            sectionOrder: 30
        );
    }
}
