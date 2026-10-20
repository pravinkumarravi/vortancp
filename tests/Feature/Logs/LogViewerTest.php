<?php

declare(strict_types=1);

namespace Tests\Feature\Logs;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Logs\Livewire\AdminLogViewer;
use App\Modules\Logs\Livewire\CustomerLogViewer;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected HostingAccount $account;
    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'Log Master Node',
            'hostname' => 'log1.vortancp.local',
            'ip_address' => '10.0.0.10',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Standard Cloud',
            'slug' => 'standard-cloud',
            'max_disk_mb' => 10000,
            'max_bandwidth_mb' => 50000,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Log Corp',
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
            'username' => 'logcorp',
            'primary_domain' => 'logcorp.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1055,
            'gid' => 1055,
            'homedir' => '/var/vortan/users/logcorp',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'logcorp.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/var/vortan/users/logcorp/public_html',
            'php_version' => '8.3',
            'status' => Domain::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_view_system_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/logs');

        $response->assertOk()
            ->assertSee('System Logs Inspector')
            ->assertSee('Nginx Error Log')
            ->assertSee('Nginx Global Access Log')
            ->assertSee('PHP-FPM Process Log')
            ->assertSee('MariaDB Database Log');
    }

    public function test_customer_is_forbidden_from_admin_logs(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/admin/logs');

        $response->assertForbidden();
    }

    public function test_customer_can_view_domain_logs_page(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/app/logs');

        $response->assertOk()
            ->assertSee('Domain Access & Error Logs')
            ->assertSee('logcorp.com');
    }

    public function test_admin_can_filter_logs_by_source_and_level_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AdminLogViewer::class)
            ->assertSee('Nginx Error Log')
            ->set('source', 'php-fpm')
            ->assertSee('PHP-FPM Process Log')
            ->set('level', 'NOTICE')
            ->set('lines', 50)
            ->assertSet('lines', 50);
    }

    public function test_customer_can_toggle_access_and_error_logs_via_livewire(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(CustomerLogViewer::class)
            ->assertSee('logcorp.com')
            ->assertSet('type', 'access')
            ->set('type', 'error')
            ->assertSet('type', 'error')
            ->set('search', 'PHP')
            ->assertSet('search', 'PHP');
    }

    public function test_admin_can_download_system_log(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AdminLogViewer::class)
            ->set('source', 'nginx-error')
            ->call('downloadLog')
            ->assertFileDownloaded();
    }

    public function test_customer_can_download_domain_log(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(CustomerLogViewer::class)
            ->set('type', 'access')
            ->call('downloadLog')
            ->assertFileDownloaded();
    }
}
