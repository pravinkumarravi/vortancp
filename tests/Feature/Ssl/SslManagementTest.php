<?php

declare(strict_types=1);

namespace Tests\Feature\Ssl;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Domains\Models\VirtualHost;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use App\Modules\Ssl\Livewire\SslCertificateManager;
use App\Modules\Ssl\Models\SslCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SslManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private Domain $domain;
    private HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'Web Node 01',
            'hostname' => 'web01.vortan.local',
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
            'name' => 'SSL Plan',
            'slug' => 'ssl-plan',
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
            'username' => 'acmessl',
            'primary_domain' => 'secure-acme.com',
            'status' => 'active',
            'uid' => 1005,
            'gid' => 1005,
            'homedir' => '/home/acmessl',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'secure-acme.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/home/acmessl/public_html',
            'status' => 'active',
        ]);

        VirtualHost::create([
            'domain_id' => $this->domain->id,
            'server_id' => $server->id,
            'webserver_type' => 'nginx',
            'config_path' => '/etc/nginx/sites-available/secure-acme.com.conf',
            'is_ssl_enabled' => false,
        ]);
    }

    public function test_admin_and_customer_can_view_ssl_dashboard(): void
    {
        $adminRes = $this->actingAs($this->admin)->get(route('admin.ssl.index'));
        $adminRes->assertStatus(200);
        $adminRes->assertSeeText('SSL/TLS Certificates');

        $custRes = $this->actingAs($this->customerUser)->get(route('customer.ssl.index'));
        $custRes->assertStatus(200);
        $custRes->assertSeeText('SSL/TLS Certificates');
    }

    public function test_can_issue_lets_encrypt_certificate_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SslCertificateManager::class)
            ->call('openLetsEncryptModal')
            ->set('selectedDomainId', $this->domain->id)
            ->set('includeWww', true)
            ->call('issueLetsEncrypt')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ssl_certificates', [
            'domain_id' => $this->domain->id,
            'hosting_account_id' => $this->account->id,
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $cert = SslCertificate::where('domain_id', $this->domain->id)->first();
        $this->assertNotNull($cert);
        $this->assertContains('secure-acme.com', $cert->sans);
        $this->assertContains('www.secure-acme.com', $cert->sans);

        // VHost SSL enabled
        $this->domain->virtualHost->refresh();
        $this->assertTrue($this->domain->virtualHost->is_ssl_enabled);
    }

    public function test_can_revoke_ssl_certificate(): void
    {
        // Issue cert first
        Livewire::actingAs($this->admin)
            ->test(SslCertificateManager::class)
            ->set('selectedDomainId', $this->domain->id)
            ->call('issueLetsEncrypt');

        $cert = SslCertificate::where('domain_id', $this->domain->id)->first();

        // Revoke
        Livewire::actingAs($this->admin)
            ->test(SslCertificateManager::class)
            ->call('revokeCertificate', $cert->id)
            ->assertHasNoErrors();

        $cert->refresh();
        $this->assertSame(SslCertificate::STATUS_REVOKED, $cert->status);

        $this->domain->virtualHost->refresh();
        $this->assertFalse($this->domain->virtualHost->is_ssl_enabled);
    }
}
