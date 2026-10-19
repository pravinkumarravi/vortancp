<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use App\Models\User;
use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        $this->assertTrue($user->hasRole(Role::SUPER_ADMIN));
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_super_admin_bypasses_all_permission_gates(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('any_arbitrary_permission'));
    }

    public function test_customer_role_only_has_assigned_permissions(): void
    {
        $customer = User::factory()->create();
        $customerRole = Role::firstOrCreate(['name' => Role::CUSTOMER], ['label' => 'Customer']);

        $domainPerm = Permission::firstOrCreate(['name' => 'domains.create'], ['label' => 'Create Domains']);
        $serverPerm = Permission::firstOrCreate(['name' => 'servers.manage'], ['label' => 'Manage Servers']);

        $customerRole->permissions()->sync([$domainPerm->id]);
        $customer->assignRole($customerRole);

        $this->actingAs($customer);

        $this->assertTrue(Gate::allows('domains.create'));
        $this->assertFalse(Gate::allows('servers.manage'));
    }
}
