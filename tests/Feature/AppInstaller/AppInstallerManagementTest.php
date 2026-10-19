<?php

declare(strict_types=1);

namespace Tests\Feature\AppInstaller;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\AppInstaller\Livewire\AppInstallerIndex;
use App\Modules\AppInstaller\Models\InstalledApp;
use App\Modules\AppInstaller\Services\AppInstallerService;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class AppInstallerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected HostingAccount $account;
    protected Domain $domain;
    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'App Test Server',
            'hostname' => 'apptest.vortancp.local',
            'ip_address' => '10.0.0.52',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'App Plan',
            'slug' => 'app-plan',
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
            'name' => 'App Test Corp',
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

        $this->testDir = sys_get_temp_dir() . '/vortan_apps_test_' . uniqid();
        File::makeDirectory($this->testDir, 0755, true);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => 'appuser',
            'primary_domain' => 'myappsite.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1027,
            'gid' => 1027,
            'homedir' => $this->testDir,
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'myappsite.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => "{$this->testDir}/public_html",
            'php_version' => '8.3',
            'status' => Domain::STATUS_ACTIVE,
        ]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }
        parent::tearDown();
    }

    public function test_can_render_app_installer_index_as_admin_and_customer(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.apps.index'))
            ->assertOk();

        $this->actingAs($this->customerUser)
            ->get(route('customer.apps.index'))
            ->assertOk();
    }

    public function test_installs_laravel_with_database_and_env(): void
    {
        $installer = app(AppInstallerService::class);

        $app = $installer->install(
            domain: $this->domain,
            appSlug: 'laravel',
            adminEmail: 'dev@myappsite.com',
            subPath: ''
        );

        $this->assertInstanceOf(InstalledApp::class, $app);
        $this->assertEquals('Laravel Framework', $app->app_name);
        $this->assertNotEmpty($app->db_name);
        $this->assertNotEmpty($app->db_user);
        $this->assertEquals(InstalledApp::STATUS_INSTALLED, $app->status);

        $envFile = "{$this->domain->document_root}/.env";
        $this->assertTrue(File::exists($envFile));
        $content = File::get($envFile);
        $this->assertStringContainsString("DB_DATABASE={$app->db_name}", $content);
        $this->assertStringContainsString("DB_USERNAME={$app->db_user}", $content);
    }

    public function test_installs_standalone_application(): void
    {
        $installer = app(AppInstallerService::class);

        $app = $installer->install(
            domain: $this->domain,
            appSlug: 'phpmyadmin',
            adminEmail: 'dba@myappsite.com',
            subPath: 'pma'
        );

        $this->assertInstanceOf(InstalledApp::class, $app);
        $this->assertNull($app->db_name);
        $this->assertTrue(File::exists("{$this->domain->document_root}/pma/index.php"));
    }

    public function test_uninstalls_application_and_deletes_record(): void
    {
        $installer = app(AppInstallerService::class);

        $app = $installer->install(
            domain: $this->domain,
            appSlug: 'phpmyadmin',
            adminEmail: 'dba@myappsite.com',
            subPath: 'pma_del'
        );

        $id = $app->id;
        $path = $app->install_path;

        $installer->uninstall($app, removeFiles: true);

        $this->assertNull(InstalledApp::find($id));
        $this->assertFalse(File::isDirectory($path));
    }

    public function test_livewire_component_interactivity(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(AppInstallerIndex::class, ['accountId' => $this->account->id])
            ->assertSet('selectedAccountId', $this->account->id)
            ->set('installing_slug', 'phpmyadmin')
            ->set('admin_email', 'admin@livewire.com')
            ->set('sub_path', 'db-admin')
            ->set('target_domain_id', $this->domain->id)
            ->call('installApp')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('installed_apps', [
            'hosting_account_id' => $this->account->id,
            'app_slug' => 'phpmyadmin',
        ]);
    }
}
