<?php

declare(strict_types=1);

namespace App\Modules\WordPressToolkit\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\WordPressToolkit\Services\WordPressConfigCompilerService;
use App\Modules\WordPressToolkit\Services\WordPressManagerService;
use App\Modules\WordPressToolkit\WordPressModule;
use Illuminate\Support\ServiceProvider;

class WordPressToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WordPressConfigCompilerService::class);
        $this->app->singleton(WordPressManagerService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(WordPressModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'wordpress-toolkit');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: WordPress Toolkit
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_wordpress', 'WordPress Toolkit', 'admin.wordpress.index', 'squares-2x2', 12)
                ->permission('wordpress.manage')
                ->activeWhen('admin.wordpress*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: WordPress Toolkit
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'applications',
            item: NavigationItem::make('customer_wordpress', 'WordPress Toolkit', 'customer.wordpress.index', 'squares-2x2', 1)
                ->permission('wordpress.manage')
                ->activeWhen('customer.wordpress*'),
            sectionTitle: 'Applications',
            sectionOrder: 35
        );
    }
}
