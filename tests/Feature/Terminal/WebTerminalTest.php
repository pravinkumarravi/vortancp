<?php

declare(strict_types=1);

namespace Tests\Feature\Terminal;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use App\Modules\Terminal\Livewire\WebTerminal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class WebTerminalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'Terminal Test Server',
            'hostname' => 'terminal.vortancp.local',
            'ip_address' => '10.0.0.88',
            'status' => Server::STATUS_ACTIVE,
        ]);
    }

    protected function createCustomerSetup(bool $hasSsh): HostingAccount
    {
        $plan = HostingPlan::create([
            'name' => $hasSsh ? 'Dev SSH Plan' : 'Standard Web Plan',
            'slug' => $hasSsh ? 'dev-ssh-plan' : 'std-web-plan',
            'max_disk_mb' => 5000,
            'max_bandwidth_mb' => 20000,
            'max_domains' => 2,
            'max_subdomains' => 5,
            'max_databases' => 2,
            'max_mailboxes' => 2,
            'max_ftp_accounts' => 2,
            'max_cron_jobs' => 2,
            'has_ssh_access' => $hasSsh,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Acme Terminal Client',
            'email' => $this->customerUser->email,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $this->server->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        return HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $this->server->id,
            'username' => 'acmeclient',
            'primary_domain' => 'acmeclient.local',
            'homedir' => '/var/vortan/vhosts/acmeclient',
            'ip_address' => '10.0.0.88',
            'status' => HostingAccount::STATUS_ACTIVE,
            'disk_used_mb' => 50,
            'bandwidth_used_mb' => 200,
        ]);
    }

    public function test_admin_can_view_web_terminal(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/terminal');

        $response->assertOk()
            ->assertSee('Web Terminal Console')
            ->assertSee('Active PTY');
    }

    public function test_customer_without_ssh_entitlement_sees_upgrade_notice(): void
    {
        $this->createCustomerSetup(hasSsh: false);

        $response = $this->actingAs($this->customerUser)->get('/app/terminal');

        $response->assertOk()
            ->assertSee('SSH & Web Terminal Access Restricted', false)
            ->assertSee('View Hosting Plan Quotas');
    }

    public function test_customer_with_ssh_entitlement_can_view_web_terminal(): void
    {
        $this->createCustomerSetup(hasSsh: true);

        $response = $this->actingAs($this->customerUser)->get('/app/terminal');

        $response->assertOk()
            ->assertSee('Web Terminal Console')
            ->assertSee('Quick Commands:');
    }

    public function test_can_execute_safe_command_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(WebTerminal::class)
            ->set('commandInput', 'php -v')
            ->call('execute')
            ->assertSee('PHP')
            ->assertSee('Exit: 0');
    }

    public function test_can_run_preset_command(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(WebTerminal::class)
            ->call('runPreset', 'pwd')
            ->assertSee('Exit: 0');
    }

    public function test_blocks_prohibited_destructive_commands_for_customer(): void
    {
        $this->createCustomerSetup(hasSsh: true);

        Livewire::actingAs($this->customerUser)
            ->test(WebTerminal::class)
            ->set('commandInput', 'sudo systemctl stop nginx')
            ->call('execute')
            ->assertSee('security violation: command execution blocked')
            ->assertSee('Exit: 1');

        Livewire::actingAs($this->customerUser)
            ->test(WebTerminal::class)
            ->set('commandInput', 'rm -rf /')
            ->call('execute')
            ->assertSee('security violation: command execution blocked')
            ->assertSee('Exit: 1');
    }

    public function test_prevents_customer_jail_escape_via_cd(): void
    {
        $this->createCustomerSetup(hasSsh: true);

        // Attempting to cd out to root or Windows drive root
        $target = PHP_OS_FAMILY === 'Windows' ? 'C:\\Windows' : '/etc';

        Livewire::actingAs($this->customerUser)
            ->test(WebTerminal::class)
            ->set('commandInput', "cd {$target}")
            ->call('execute')
            ->assertSee('permission denied: navigation outside account home directory is prohibited')
            ->assertSee('Exit: 1');
    }

    public function test_can_clear_screen_and_reset_terminal(): void
    {
        $test = Livewire::actingAs($this->adminUser)
            ->test(WebTerminal::class)
            ->call('clearScreen');

        $this->assertEmpty($test->get('history'));

        $test->call('resetTerminal');
        $this->assertNotEmpty($test->get('history'));
        $test->assertSee('Terminal session reset');
    }

    public function test_records_audit_log_upon_command_execution(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(WebTerminal::class)
            ->set('commandInput', 'pwd')
            ->call('execute');

        $log = AuditLog::where('event', 'terminal.command_executed')->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->adminUser->id, $log->user_id);
        $this->assertEquals('pwd', $log->new_values['command']);
    }
}
