<?php

declare(strict_types=1);

namespace Tests\Feature\Databases;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Databases\Livewire\DatabaseIndex;
use App\Modules\Databases\Models\Database;
use App\Modules\Databases\Models\DatabaseUser;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'DB Master',
            'hostname' => 'dbmaster.vortan.local',
            'ip_address' => '10.0.0.12',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Acme Web Client',
            'email' => 'customer@vortan.local',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Database Tier',
            'slug' => 'database-tier',
            'disk_quota_mb' => 10240,
            'bandwidth_quota_mb' => 102400,
            'max_domains' => 5,
            'max_subdomains' => 5,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
            'has_ssh_access' => true,
            'has_ssl' => true,
            'has_git' => true,
            'has_dns_management' => true,
            'has_wordpress_toolkit' => true,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $server->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => 'acmedb',
            'primary_domain' => 'acme-db.com',
            'status' => 'active',
            'uid' => 1025,
            'gid' => 1025,
            'homedir' => '/home/acmedb',
        ]);
    }

    public function test_admin_and_customer_can_view_databases(): void
    {
        $adminRes = $this->actingAs($this->admin)->get(route('admin.databases.index'));
        $adminRes->assertStatus(200);
        $adminRes->assertSeeText('Databases & Users');

        $custRes = $this->actingAs($this->customerUser)->get(route('customer.databases.index'));
        $custRes->assertStatus(200);
        $custRes->assertSeeText('Databases & Users');
    }

    public function test_can_create_database_user_and_assign_via_livewire(): void
    {
        // 1. Create Database
        Livewire::actingAs($this->admin)
            ->test(DatabaseIndex::class, ['accountId' => $this->account->id])
            ->call('openCreateDbModal')
            ->set('dbSuffix', 'store')
            ->set('dbEngine', 'mysql')
            ->call('createDatabase')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('databases', [
            'name' => 'acmedb_store',
            'hosting_account_id' => $this->account->id,
            'db_engine' => 'mysql',
        ]);

        $db = Database::where('name', 'acmedb_store')->first();

        // 2. Create Database User
        Livewire::actingAs($this->admin)
            ->test(DatabaseIndex::class, ['accountId' => $this->account->id])
            ->call('openCreateUserModal')
            ->set('userSuffix', 'dbusr')
            ->set('userPassword', 'VortanSecurePass#2026')
            ->set('userHost', 'localhost')
            ->call('createDatabaseUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => 'acmedb_dbusr',
            'hosting_account_id' => $this->account->id,
        ]);

        $user = DatabaseUser::where('username', 'acmedb_dbusr')->first();

        // 3. Assign User to Database
        Livewire::actingAs($this->admin)
            ->test(DatabaseIndex::class, ['accountId' => $this->account->id])
            ->set('assignDbId', $db->id)
            ->set('assignUserId', $user->id)
            ->set('selectedPrivileges', ['ALL'])
            ->call('assignUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('database_user_assignments', [
            'database_id' => $db->id,
            'database_user_id' => $user->id,
        ]);

        // 4. Delete Database
        Livewire::actingAs($this->admin)
            ->test(DatabaseIndex::class, ['accountId' => $this->account->id])
            ->call('deleteDatabase', $db->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('databases', ['id' => $db->id]);
    }
}
