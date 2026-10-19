<?php

declare(strict_types=1);

namespace App\Modules\Audit\Providers;

use App\Modules\Audit\AuditModule;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, function () {
            return new AuditLogger();
        });

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(AuditModule::class);
    }

    public function boot(): void
    {
        $navigation = $this->app->make(NavigationRegistry::class);

        // Register Audit Log item under Admin Navigation
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'monitoring_logs',
            item: NavigationItem::make('audit_logs', 'Audit Logs', 'admin.audit.index', 'clipboard-document-list', 5)
                ->permission('audit.view')
                ->activeWhen('admin.audit*'),
            sectionTitle: 'Monitoring & Logs',
            sectionOrder: 70
        );
    }
}
