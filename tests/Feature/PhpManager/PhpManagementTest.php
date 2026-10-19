<?php

declare(strict_types=1);

namespace Tests\Feature\PhpManager;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\PhpManager\Livewire\PhpManagerIndex;
use App\Modules\PhpManager\Models\PhpPool;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhpManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private HostingAccount $account;
    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'PHP App Server',
            'hostname' => 'apps01.vortan.local',
            'ip_address' => '10.0.0.40',
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
            'name' => 'PHP Plan',
            'slug' => 'php-plan',
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
            'username' => 'acmephp',
            'primary_domain' => 'acme-apps.io',
            'status' => 'active',
            'uid' => 1045,
            'gid' => 1045,
            'homedir' => '/home/acmephp',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'acme-apps.io',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/home/acmephp/public_html',
            'php_version' => '8.4',
            'status' => 'active',
        ]);
    }

    public function test_admin_and_customer_can_view_php_manager(): void
    {
        $adminRes = $this->actingAs($this->admin)->get(route('admin.php.index'));
        $adminRes->assertStatus(200);
        $adminRes->assertSeeText('PHP Multi-Version Manager');

        $custRes = $this->actingAs($this->customerUser)->get(route('customer.php.index'));
        $custRes->assertStatus(200);
        $custRes->assertSeeText('PHP Multi-Version Manager');
    }

    public function test_can_switch_domain_php_version_and_save_directives_via_livewire(): void
    {
        // 1. Switch domain to PHP 8.3
        Livewire::actingAs($this->admin)
            ->test(PhpManagerIndex::class, ['accountId' => $this->account->id])
            ->call('openVersionModal', $this->domain->id)
            ->set('targetVersion', '8.3')
            ->call('saveVersion')
            ->assertHasNoErrors();

        $this->domain->refresh();
        $this->assertSame('8.3', $this->domain->php_version);

        // 2. Ensure pool was generated and edit directives
        $pool = PhpPool::where('hosting_account_id', $this->account->id)
            ->where('version', '8.3')
            ->first();
        $this->assertNotNull($pool);

        Livewire::actingAs($this->admin)
            ->test(PhpManagerIndex::class, ['accountId' => $this->account->id])
            ->call('openDirectivesModal', $pool->id)
            ->set('memoryLimit', '512M')
            ->set('maxExecutionTime', 180)
            ->call('saveDirectives')
            ->assertHasNoErrors();

        $pool->refresh();
        $this->assertSame('512M', $pool->memory_limit);
        $this->assertSame(180, $pool->max_execution_time);
    }
}
