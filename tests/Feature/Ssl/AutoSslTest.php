<?php

declare(strict_types=1);

namespace Tests\Feature\Ssl;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use App\Modules\Ssl\Livewire\AutoSslManager;
use App\Modules\Ssl\Models\SslCertificate;
use App\Modules\Ssl\Services\AutoSslFleetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AutoSslTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Domain $domain;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'AutoSSL Master Node',
            'hostname' => 'ssl1.vortancp.local',
            'ip_address' => '10.0.0.12',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'AutoSSL Enabled Plan',
            'slug' => 'autossl-plan',
            'max_disk_mb' => 10000,
            'max_bandwidth_mb' => 50000,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
            'has_ssl' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'SSL Client',
            'email' => $this->customerUser->email,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $server->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => 'sslclient',
            'primary_domain' => 'sslclient.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1056,
            'gid' => 1056,
            'homedir' => '/var/vortan/users/sslclient',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'sslclient.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/var/vortan/users/sslclient/public_html',
            'php_version' => '8.3',
            'status' => Domain::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_view_autossl_manager_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/ssl/autossl');

        $response->assertOk()
            ->assertSee('AutoSSL Fleet Manager')
            ->assertSee('sslclient.com')
            ->assertSee('Unsecured');
    }

    public function test_customer_is_forbidden_from_autossl_manager(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/admin/ssl/autossl');

        $response->assertForbidden();
    }

    public function test_fleet_overview_metrics_calculation(): void
    {
        $service = app(AutoSslFleetService::class);
        $overview = $service->getFleetOverview();

        $this->assertEquals(1, $overview['total_domains']);
        $this->assertEquals(0, $overview['secured_domains']);
        $this->assertEquals(1, $overview['unsecured_domains']);
        $this->assertEquals(0.0, $overview['coverage_pct']);
    }

    public function test_can_run_fleet_autossl_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AutoSslManager::class)
            ->call('runFleetAutoSsl')
            ->assertSee('AutoSSL completed: 1 issued');

        $this->assertDatabaseHas('ssl_certificates', [
            'domain_id' => $this->domain->id,
            'status' => SslCertificate::STATUS_ACTIVE,
        ]);

        $service = app(AutoSslFleetService::class);
        $overview = $service->getFleetOverview();

        $this->assertEquals(1, $overview['secured_domains']);
        $this->assertEquals(100.0, $overview['coverage_pct']);
    }

    public function test_can_run_autossl_for_single_domain(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AutoSslManager::class)
            ->call('runDomainAutoSsl', $this->domain->id)
            ->assertSee('Let\'s Encrypt certificate successfully provisioned');

        $this->assertDatabaseHas('ssl_certificates', [
            'domain_id' => $this->domain->id,
            'status' => SslCertificate::STATUS_ACTIVE,
        ]);
    }

    public function test_can_toggle_auto_renew_status(): void
    {
        // First issue a cert
        app(AutoSslFleetService::class)->runAutoSsl($this->domain->id);

        $cert = SslCertificate::where('domain_id', $this->domain->id)->first();
        $this->assertTrue($cert->auto_renew);

        // Toggle auto-renew off
        Livewire::actingAs($this->adminUser)
            ->test(AutoSslManager::class)
            ->call('toggleAutoRenew', $cert->id);

        $this->assertFalse($cert->fresh()->auto_renew);
    }

    public function test_autossl_artisan_command_executes_successfully(): void
    {
        $this->artisan('vortan:autossl-run')
            ->expectsOutputToContain('Starting VortanCP AutoSSL fleet scan')
            ->expectsOutputToContain('AutoSSL run completed successfully')
            ->assertExitCode(0);
    }
}
