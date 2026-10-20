<?php

declare(strict_types=1);

namespace App\Modules\EmailSecurity\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\EmailSecurity\EmailSecurityModule;
use App\Modules\EmailSecurity\Livewire\EmailSecurityManager;
use App\Modules\EmailSecurity\Services\EmailSecurityService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class EmailSecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmailSecurityService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(EmailSecurityModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'email_security');

        Livewire::component('email_security.email-security-manager', EmailSecurityManager::class);

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Email Security & Anti-Spam
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'system_settings',
            item: NavigationItem::make('admin_email_security', 'Email Security & Anti-Spam', 'admin.email_security.index', 'shield-check', 58)
                ->permission('servers.manage')
                ->activeWhen('admin.email_security*'),
            sectionTitle: 'System Settings',
            sectionOrder: 90
        );

        // Customer Navigation: Email Security & Spam
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'email_services',
            item: NavigationItem::make('customer_email_security', 'Email Security & Spam', 'customer.email_security.index', 'shield-check', 25)
                ->permission('email.manage')
                ->activeWhen('customer.email_security*'),
            sectionTitle: 'Email Services',
            sectionOrder: 20
        );
    }
}
