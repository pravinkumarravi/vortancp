<?php

declare(strict_types=1);

namespace App\Modules\Git\Providers;

use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use App\Modules\Core\Services\ModuleRegistry;
use App\Modules\Git\GitModule;
use App\Modules\Git\Services\GitDeployKeyService;
use App\Modules\Git\Services\GitRepositoryService;
use Illuminate\Support\ServiceProvider;

class GitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GitDeployKeyService::class);
        $this->app->singleton(GitRepositoryService::class);

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->registerModule(GitModule::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'git');

        $navigation = $this->app->make(NavigationRegistry::class);

        // Admin Navigation: Git Repositories
        $navigation->registerItem(
            panel: 'admin',
            sectionId: 'hosting_management',
            item: NavigationItem::make('admin_git', 'Git Repositories', 'admin.git.index', 'code-bracket', 14)
                ->permission('git.manage')
                ->activeWhen('admin.git*'),
            sectionTitle: 'Hosting Management',
            sectionOrder: 30
        );

        // Customer Navigation: Git Repositories
        $navigation->registerItem(
            panel: 'customer',
            sectionId: 'advanced_tools',
            item: NavigationItem::make('customer_git', 'Git Repositories', 'customer.git.index', 'code-bracket', 1)
                ->permission('git.manage')
                ->activeWhen('customer.git*'),
            sectionTitle: 'Advanced Tools',
            sectionOrder: 40
        );
    }
}
