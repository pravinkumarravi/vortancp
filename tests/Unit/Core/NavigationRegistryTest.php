<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use App\Modules\Core\Navigation\NavigationItem;
use App\Modules\Core\Navigation\NavigationRegistry;
use Tests\TestCase;

class NavigationRegistryTest extends TestCase
{
    protected NavigationRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new NavigationRegistry();
    }

    public function test_can_register_and_retrieve_navigation_for_panel(): void
    {
        $item = NavigationItem::make('dashboard', 'Dashboard', 'dashboard', 'home', 1);

        $this->registry->registerItem('admin', 'overview', $item, 'Overview', 1);

        $user = new User();
        $sections = $this->registry->getNavigationForUser('admin', $user);

        $this->assertCount(1, $sections);
        $this->assertEquals('Overview', $sections->first()->getTitle());
        $this->assertCount(1, $sections->first()->getAuthorizedItems($user));
        $this->assertEquals('Dashboard', $sections->first()->getAuthorizedItems($user)->first()->getTitle());
    }

    public function test_filters_by_permission_when_specified(): void
    {
        $item = NavigationItem::make('secret_node', 'Nodes', 'nodes', 'server', 1)
            ->permission('servers.manage');

        $this->registry->registerItem('admin', 'infra', $item, 'Infrastructure', 2);

        $userWithoutPermission = new class extends User {
            public function can($abilities, $arguments = []): bool
            {
                return false;
            }
        };

        $sections = $this->registry->getNavigationForUser('admin', $userWithoutPermission);
        $this->assertCount(0, $sections);

        $userWithPermission = new class extends User {
            public function can($abilities, $arguments = []): bool
            {
                return true;
            }
        };

        $authorizedSections = $this->registry->getNavigationForUser('admin', $userWithPermission);
        $this->assertCount(1, $authorizedSections);
    }
}
