<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Billing\Livewire\ApiKeyManager;
use App\Modules\Billing\Models\ApiKey;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Server $server;
    protected HostingPlan $starterPlan;
    protected HostingPlan $proPlan;
    protected string $apiToken;
    protected ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'Billing Master Node',
            'hostname' => 'node1.vortancp.local',
            'ip_address' => '192.168.10.100',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $this->starterPlan = HostingPlan::create([
            'name' => 'Starter Cloud',
            'slug' => 'starter-cloud',
            'max_disk_mb' => 5000,
            'max_bandwidth_mb' => 20000,
            'max_domains' => 1,
            'max_subdomains' => 3,
            'max_databases' => 2,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 1,
            'max_cron_jobs' => 2,
            'has_ssl' => true,
            'has_dns_management' => true,
            'is_active' => true,
        ]);

        $this->proPlan = HostingPlan::create([
            'name' => 'Pro Business',
            'slug' => 'pro-business',
            'max_disk_mb' => 25000,
            'max_bandwidth_mb' => 100000,
            'max_domains' => 10,
            'max_subdomains' => 50,
            'max_databases' => 20,
            'max_mailboxes' => 50,
            'max_ftp_accounts' => 10,
            'max_cron_jobs' => 10,
            'has_ssl' => true,
            'has_dns_management' => true,
            'is_active' => true,
        ]);

        $result = ApiKey::generate('WHMCS Primary Production', ['*']);
        $this->apiKey = $result['apiKey'];
        $this->apiToken = $result['plainTextToken'];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/billing/plans');

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_invalid_or_expired_key_is_rejected(): void
    {
        $response = $this->withHeader('X-API-Key', 'vortan_invalid_key_12345')
            ->getJson('/api/v1/billing/plans');

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');

        // Test deactivated key
        $this->apiKey->update(['is_active' => false]);

        $response2 = $this->withHeader('X-API-Key', $this->apiToken)
            ->getJson('/api/v1/billing/plans');

        $response2->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_can_list_plans_via_api(): void
    {
        $response = $this->withHeader('X-API-Key', $this->apiToken)
            ->getJson('/api/v1/billing/plans');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Starter Cloud')
            ->assertJsonPath('data.1.name', 'Pro Business');
    }

    public function test_can_create_hosting_account_via_billing_api(): void
    {
        $payload = [
            'domain' => 'clientdomain.com',
            'username' => 'clientdom',
            'customer_email' => 'client@clientdomain.com',
            'customer_name' => 'Client User',
            'plan_name' => 'Pro Business',
        ];

        $response = $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.username', 'clientdom')
            ->assertJsonPath('data.domain', 'clientdomain.com')
            ->assertJsonPath('data.plan.name', 'Pro Business');

        $this->assertDatabaseHas('hosting_accounts', [
            'username' => 'clientdom',
            'primary_domain' => 'clientdomain.com',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('domains', [
            'domain' => 'clientdomain.com',
            'type' => Domain::TYPE_PRIMARY,
        ]);

        $this->assertDatabaseHas('dns_zones', [
            'name' => 'clientdomain.com',
            'status' => 'active',
        ]);
    }

    public function test_create_account_fails_with_invalid_or_duplicate_data(): void
    {
        // 1. Missing domain
        $response1 = $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'customer_email' => 'test@example.com',
            ]);
        $response1->assertStatus(422);

        // 2. Create one valid account
        $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'firstsite.com',
                'username' => 'firstsite',
                'customer_email' => 'first@example.com',
            ])->assertStatus(201);

        // 3. Duplicate username attempt
        $response2 = $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'secondsite.com',
                'username' => 'firstsite',
                'customer_email' => 'second@example.com',
            ]);
        $response2->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_can_get_account_status(): void
    {
        $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'statuscheck.com',
                'username' => 'statususr',
                'customer_email' => 'status@example.com',
                'plan_id' => $this->proPlan->id,
            ])->assertStatus(201);

        $response = $this->withHeader('X-API-Key', $this->apiToken)
            ->getJson('/api/v1/billing/accounts/statususr/status');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.username', 'statususr')
            ->assertJsonPath('data.primary_domain', 'statuscheck.com')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_can_suspend_and_unsuspend_account(): void
    {
        $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'suspensiontest.com',
                'username' => 'suspendme',
                'customer_email' => 'suspend@example.com',
            ])->assertStatus(201);

        // Suspend
        $suspendResponse = $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/suspendme/suspend', [
                'reason' => 'Overdue invoice #1042',
            ]);

        $suspendResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'suspended')
            ->assertJsonPath('data.suspended_reason', 'Overdue invoice #1042');

        $this->assertDatabaseHas('hosting_accounts', [
            'username' => 'suspendme',
            'status' => 'suspended',
            'suspended_reason' => 'Overdue invoice #1042',
        ]);

        // Unsuspend
        $unsuspendResponse = $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/suspendme/unsuspend');

        $unsuspendResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('hosting_accounts', [
            'username' => 'suspendme',
            'status' => 'active',
            'suspended_reason' => null,
        ]);
    }

    public function test_can_change_account_package(): void
    {
        $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'upgradeplan.com',
                'username' => 'upgrademe',
                'customer_email' => 'upgrade@example.com',
                'plan_name' => 'Starter Cloud',
            ])->assertStatus(201);

        // Upgrade to Pro Business
        $response = $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/upgrademe/change-package', [
                'plan_name' => 'Pro Business',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.plan.name', 'Pro Business');

        $account = HostingAccount::where('username', 'upgrademe')->first();
        $this->assertEquals($this->proPlan->id, $account->subscription->hosting_plan_id);
    }

    public function test_can_list_accounts_with_pagination(): void
    {
        $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'listacc1.com',
                'username' => 'listacc1',
                'customer_email' => 'acc1@example.com',
            ])->assertStatus(201);

        $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'listacc2.com',
                'username' => 'listacc2',
                'customer_email' => 'acc2@example.com',
            ])->assertStatus(201);

        $response = $this->withHeader('X-API-Key', $this->apiToken)
            ->getJson('/api/v1/billing/accounts?per_page=10');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_terminate_account(): void
    {
        $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/create', [
                'domain' => 'deleteme.com',
                'username' => 'terminateme',
                'customer_email' => 'terminate@example.com',
            ])->assertStatus(201);

        $this->assertDatabaseHas('hosting_accounts', ['username' => 'terminateme']);

        $response = $this->withHeader('X-API-Key', $this->apiToken)
            ->postJson('/api/v1/billing/accounts/terminateme/terminate');

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('hosting_accounts', ['username' => 'terminateme']);
        $this->assertDatabaseMissing('domains', ['domain' => 'deleteme.com']);
    }

    public function test_admin_can_manage_api_keys_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(ApiKeyManager::class)
            ->assertSee('API Keys')
            ->assertSee('Billing Integration')
            ->assertSee('WHMCS Primary Production')
            ->set('keyName', 'Blesta Staging Key')
            ->set('selectedAbility', '*')
            ->call('generateKey')
            ->assertSee('API Key Generated for [Blesta Staging Key]')
            ->assertSee('Blesta Staging Key');

        $createdKey = ApiKey::where('name', 'Blesta Staging Key')->first();
        $this->assertNotNull($createdKey);
        $this->assertTrue($createdKey->is_active);

        // Toggle status
        Livewire::actingAs($this->adminUser)
            ->test(ApiKeyManager::class)
            ->call('toggleKeyStatus', $createdKey->id);

        $this->assertFalse($createdKey->fresh()->is_active);

        // Delete key
        Livewire::actingAs($this->adminUser)
            ->test(ApiKeyManager::class)
            ->call('deleteKey', $createdKey->id);

        $this->assertDatabaseMissing('api_keys', ['id' => $createdKey->id]);
    }
}
