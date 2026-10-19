<?php

declare(strict_types=1);

namespace Tests\Feature\WordPressToolkit;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use App\Modules\WordPressToolkit\Livewire\WordPressIndex;
use App\Modules\WordPressToolkit\Models\WordPressInstance;
use App\Modules\WordPressToolkit\Services\WordPressManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class WordPressManagementTest extends TestCase
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
            'name' => 'WP Test Server',
            'hostname' => 'wptest.vortancp.local',
            'ip_address' => '10.0.0.50',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'WP Enabled Plan',
            'slug' => 'wp-plan',
            'max_disk_mb' => 10000,
            'max_bandwidth_mb' => 50000,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
            'has_wordpress_toolkit' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'WP Test Corp',
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
            'username' => 'wpuser',
            'primary_domain' => 'mywpsite.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1025,
            'gid' => 1025,
            'homedir' => '/home/wpuser',
        ]);

        $this->testDir = sys_get_temp_dir() . '/vortan_wp_test_' . uniqid();
        File::makeDirectory($this->testDir, 0755, true);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'mywpsite.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => $this->testDir,
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

    public function test_can_render_wordpress_index_as_admin_and_customer(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.wordpress.index'))
            ->assertOk();

        $this->actingAs($this->customerUser)
            ->get(route('customer.wordpress.index'))
            ->assertOk();
    }

    public function test_installs_wordpress_instance_and_provisions_database(): void
    {
        $manager = app(WordPressManagerService::class);

        $instance = $manager->install(
            domain: $this->domain,
            siteTitle: 'My Corporate Blog',
            adminEmail: 'boss@mywpsite.com',
            subPath: '',
            autoUpdateCore: true
        );

        $this->assertInstanceOf(WordPressInstance::class, $instance);
        $this->assertEquals('My Corporate Blog', $instance->site_title);
        $this->assertEquals('http://mywpsite.com', $instance->site_url);
        $this->assertEquals(WordPressInstance::STATUS_ACTIVE, $instance->status);
        $this->assertTrue(File::exists("{$this->testDir}/wp-config.php"));
        $this->assertTrue(File::exists("{$this->testDir}/index.php"));

        $configContent = File::get("{$this->testDir}/wp-config.php");
        $this->assertStringContainsString("define('DB_NAME', '{$instance->db_name}');", $configContent);
        $this->assertStringContainsString("define('DISALLOW_FILE_EDIT', true);", $configContent);
    }

    public function test_prevents_installation_when_plan_does_not_permit_wordpress(): void
    {
        $planWithoutWp = HostingPlan::create([
            'name' => 'No WP Plan',
            'slug' => 'no-wp-plan',
            'max_disk_mb' => 1000,
            'max_bandwidth_mb' => 5000,
            'has_wordpress_toolkit' => false,
            'is_active' => true,
        ]);

        $this->account->subscription->update(['hosting_plan_id' => $planWithoutWp->id]);

        $this->expectException(QuotaExceededException::class);

        $manager = app(WordPressManagerService::class);
        $manager->install(
            domain: $this->domain,
            siteTitle: 'Unauthorized Blog',
            adminEmail: 'unauth@test.com'
        );
    }

    public function test_toggle_maintenance_mode(): void
    {
        $manager = app(WordPressManagerService::class);

        $instance = $manager->install(
            domain: $this->domain,
            siteTitle: 'Maintenance Blog',
            adminEmail: 'test@blog.com'
        );

        // Turn maintenance ON
        $manager->toggleMaintenance($instance);
        $instance->refresh();

        $this->assertEquals(WordPressInstance::STATUS_MAINTENANCE, $instance->status);
        $this->assertTrue(File::exists("{$this->testDir}/.maintenance"));

        // Turn maintenance OFF
        $manager->toggleMaintenance($instance);
        $instance->refresh();

        $this->assertEquals(WordPressInstance::STATUS_ACTIVE, $instance->status);
        $this->assertFalse(File::exists("{$this->testDir}/.maintenance"));
    }

    public function test_toggle_hardening_flags(): void
    {
        $manager = app(WordPressManagerService::class);

        $instance = $manager->install(
            domain: $this->domain,
            siteTitle: 'Hardened Blog',
            adminEmail: 'test@blog.com'
        );

        $this->assertTrue($instance->xmlrpc_blocked);
        $this->assertTrue($instance->file_editing_disabled);

        $manager->toggleHardening($instance, 'xmlrpc');
        $instance->refresh();
        $this->assertFalse($instance->xmlrpc_blocked);

        $manager->toggleHardening($instance, 'file_editing');
        $instance->refresh();
        $this->assertFalse($instance->file_editing_disabled);
    }

    public function test_generates_sso_login_url(): void
    {
        $manager = app(WordPressManagerService::class);

        $instance = $manager->install(
            domain: $this->domain,
            siteTitle: 'SSO Blog',
            adminEmail: 'test@blog.com'
        );

        $url = $manager->generateSsoUrl($instance);
        $this->assertStringContainsString('http://mywpsite.com/wp-login.php?vortan_sso=', $url);
    }

    public function test_deletes_wordpress_instance(): void
    {
        $manager = app(WordPressManagerService::class);

        $instance = $manager->install(
            domain: $this->domain,
            siteTitle: 'To Delete Blog',
            adminEmail: 'delete@blog.com'
        );

        $id = $instance->id;
        $manager->deleteInstance($instance);

        $this->assertNull(WordPressInstance::find($id));
    }

    public function test_livewire_component_interactivity(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(WordPressIndex::class, ['accountId' => $this->account->id])
            ->assertSet('selectedAccountId', $this->account->id)
            ->set('new_site_title', 'Livewire Created WP')
            ->set('new_admin_email', 'admin@livewire.com')
            ->call('installWordPress')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wordpress_instances', [
            'hosting_account_id' => $this->account->id,
            'site_title' => 'Livewire Created WP',
        ]);
    }
}
