<?php

declare(strict_types=1);

namespace Tests\Feature\Domains;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Livewire\DomainIndex;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DomainManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private HostingAccount $account;
    private Domain $primaryDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'Edge Server 01',
            'hostname' => 'edge01.vortan.local',
            'ip_address' => '10.0.0.1',
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
            'name' => 'Standard Cloud',
            'slug' => 'standard',
            'disk_quota_mb' => 10240,
            'bandwidth_quota_mb' => 102400,
            'max_domains' => 3,
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
            'username' => 'acmeclient',
            'primary_domain' => 'acme-client.com',
            'status' => 'active',
            'uid' => 1001,
            'gid' => 1001,
            'homedir' => '/home/acmeclient',
        ]);

        $this->primaryDomain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'acme-client.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/home/acmeclient/public_html',
            'php_version' => '8.4',
            'status' => Domain::STATUS_ACTIVE,
            'force_https' => true,
        ]);
    }

    public function test_admin_can_view_domains_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.domains.index'));
        $response->assertStatus(200);
        $response->assertSeeText('Domains & Virtual Hosts');
        $response->assertSee('acme-client.com');
    }

    public function test_customer_can_view_domains_page(): void
    {
        $response = $this->actingAs($this->customerUser)->get(route('customer.domains.index'));
        $response->assertStatus(200);
        $response->assertSee('acme-client.com');
    }

    public function test_can_provision_addon_domain_and_virtual_host_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DomainIndex::class)
            ->call('openCreateModal')
            ->set('selectedAccountId', $this->account->id)
            ->set('domainType', Domain::TYPE_ADDON)
            ->set('domainName', 'newsite.org')
            ->set('phpVersion', '8.4')
            ->call('createDomain')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('domains', [
            'domain' => 'newsite.org',
            'type' => Domain::TYPE_ADDON,
            'hosting_account_id' => $this->account->id,
            'document_root' => '/home/acmeclient/newsite.org',
        ]);

        $this->assertDatabaseHas('virtual_hosts', [
            'config_path' => '/etc/nginx/sites-available/newsite.org.conf',
            'webserver_type' => 'nginx',
        ]);
    }

    public function test_can_provision_subdomain_attached_to_parent(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DomainIndex::class)
            ->call('openCreateModal')
            ->set('selectedAccountId', $this->account->id)
            ->set('domainType', Domain::TYPE_SUBDOMAIN)
            ->set('domainName', 'api')
            ->set('parentDomainId', $this->primaryDomain->id)
            ->set('phpVersion', '8.3')
            ->call('createDomain')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('domains', [
            'domain' => 'api.acme-client.com',
            'type' => Domain::TYPE_SUBDOMAIN,
            'parent_domain_id' => $this->primaryDomain->id,
            'php_version' => '8.3',
        ]);
    }

    public function test_can_delete_addon_domain_but_not_primary(): void
    {
        $addon = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'temp-delete.com',
            'type' => Domain::TYPE_ADDON,
            'document_root' => '/home/acmeclient/temp-delete.com',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(DomainIndex::class)
            ->call('triggerDelete', $addon->id)
            ->call('confirmDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('domains', ['domain' => 'temp-delete.com']);
    }
}
