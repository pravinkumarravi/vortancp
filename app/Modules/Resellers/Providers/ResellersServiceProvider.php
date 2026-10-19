<?php

declare(strict_types=1);

namespace App\Modules\Resellers\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Resellers\ResellersModule;
use Illuminate\Support\ServiceProvider;

class ResellersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(ResellersModule::class);
    }

    public function boot(): void
    {
        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Resellers
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('resellers_list', 'Resellers Center', 'admin.resellers.index', 'briefcase', 3)
                ->permission('resellers.view')
                ->activeWhen('admin.resellers*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );
    }
}
