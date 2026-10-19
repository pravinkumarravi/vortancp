<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Models\User;
use App\Modules\Accounts\Livewire\AccountIndex;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Identity\Models\Role;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private Customer $customer;
    private HostingPlan $plan;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Acme Corporation',
            'email' => 'billing@acme.com',
            'status' => 'active',
        ]);

        $this->plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud',
            'disk_quota_mb' => 10240,
            'bandwidth_quota_mb' => 102400,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 20,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 10,
            'has_ssh_access' => true,
            'has_ssl' => true,
            'has_git' => true,
            'has_dns_management' => true,
            'has_wordpress_toolkit' => true,
            'is_active' => true,
        ]);

        $this->server = Server::create([
            'name' => 'Node Alpha',
            'hostname' => 'node-a.vortan.local',
            'ip_address' => '192.168.1.100',
            'status' => 'active',
            'is_local' => true,
        ]);
    }

    public function test_admin_can_view_accounts_inventory(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.accounts.index'));
        $response->assertStatus(200);
        $response->assertSee('Hosting Accounts');
    }

    public function test_customer_is_forbidden_from_admin_accounts(): void
    {
        $response = $this->actingAs($this->customerUser)->get(route('admin.accounts.index'));
        $response->assertStatus(403);
    }

    public function test_can_provision_account_and_toggle_suspension_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AccountIndex::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedPlanId', $this->plan->id)
            ->set('selectedServerId', $this->server->id)
            ->set('domain', 'newclient.com')
            ->call('createAccount')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hosting_accounts', [
            'primary_domain' => 'newclient.com',
            'username' => 'newclien', // 8 chars from newclient
            'status' => HostingAccount::STATUS_ACTIVE,
        ]);

        $account = HostingAccount::where('username', 'newclien')->first();
        $this->assertNotNull($account);

        // Suspend
        Livewire::actingAs($this->admin)
            ->test(AccountIndex::class)
            ->call('triggerSuspend', $account->id)
            ->set('suspendReason', 'Terms violation')
            ->call('toggleAccountStatus');

        $account->refresh();
        $this->assertSame(HostingAccount::STATUS_SUSPENDED, $account->status);
        $this->assertSame('Terms violation', $account->suspended_reason);

        // Unsuspend
        Livewire::actingAs($this->admin)
            ->test(AccountIndex::class)
            ->call('triggerSuspend', $account->id)
            ->call('toggleAccountStatus');

        $account->refresh();
        $this->assertSame(HostingAccount::STATUS_ACTIVE, $account->status);
        $this->assertNull($account->suspended_reason);
    }
}
