<?php

declare(strict_types=1);

namespace Tests\Feature\SecurityCenter;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\SecurityCenter\Livewire\SecurityCenterIndex;
use App\Modules\SecurityCenter\Models\QuarantinedFile;
use App\Modules\SecurityCenter\Models\WafConfiguration;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityCenterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Server $server;
    protected string $testSandbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'WAF Master Node',
            'hostname' => 'wafmaster.vortancp.local',
            'ip_address' => '10.0.0.25',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $this->testSandbox = str_replace('\\', '/', storage_path('sandboxes/test_security'));
        if (! File::isDirectory($this->testSandbox)) {
            File::makeDirectory($this->testSandbox, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testSandbox)) {
            File::deleteDirectory($this->testSandbox);
        }

        $quarantineBase = storage_path('app/quarantine');
        if (File::isDirectory($quarantineBase)) {
            File::deleteDirectory($quarantineBase);
        }

        parent::tearDown();
    }

    public function test_admin_can_view_security_center_and_customer_is_forbidden(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.security.index'))
            ->assertOk()
            ->assertSeeText('Security Center & WAF');

        $this->actingAs($this->customerUser)
            ->get(route('admin.security.index'))
            ->assertForbidden();
    }

    public function test_can_save_waf_settings_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(SecurityCenterIndex::class, ['serverId' => $this->server->id])
            ->set('waf_is_enabled', true)
            ->set('paranoia_level', 3)
            ->set('block_sqli', true)
            ->set('block_xss', true)
            ->set('anomaly_threshold', 8)
            ->call('saveWafSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waf_configurations', [
            'server_id' => $this->server->id,
            'paranoia_level' => 3,
            'anomaly_threshold' => 8,
            'is_enabled' => true,
        ]);
    }

    public function test_can_run_malware_scan_and_manage_quarantine_via_livewire(): void
    {
        // 1. Plant an infected webshell in test sandbox
        $maliciousFile = "{$this->testSandbox}/c99_shell.php";
        File::put($maliciousFile, '<?php eval(base64_decode("c3lzdGVtKCk7")); ?>');

        // Clean file
        $cleanFile = "{$this->testSandbox}/index.php";
        File::put($cleanFile, '<?php echo "Hello World"; ?>');

        // 2. Run scan
        Livewire::actingAs($this->adminUser)
            ->test(SecurityCenterIndex::class, ['serverId' => $this->server->id])
            ->set('scan_path', $this->testSandbox)
            ->call('runScan')
            ->assertHasNoErrors();

        // Malicious file should be moved into quarantine
        $this->assertFileDoesNotExist($maliciousFile);
        $this->assertFileExists($cleanFile);

        $this->assertDatabaseHas('quarantined_files', [
            'original_path' => $maliciousFile,
            'threat_name' => 'PHP.Webshell.Generic.EvalB64',
            'status' => 'quarantined',
        ]);

        $quarantined = QuarantinedFile::where('original_path', $maliciousFile)->first();

        // 3. Restore file
        Livewire::actingAs($this->adminUser)
            ->test(SecurityCenterIndex::class, ['serverId' => $this->server->id])
            ->call('restoreQuarantine', $quarantined->id)
            ->assertHasNoErrors();

        $this->assertFileExists($maliciousFile);
        $quarantined->refresh();
        $this->assertEquals('restored', $quarantined->status);

        // 4. Delete file
        Livewire::actingAs($this->adminUser)
            ->test(SecurityCenterIndex::class, ['serverId' => $this->server->id])
            ->call('deleteQuarantine', $quarantined->id)
            ->assertHasNoErrors();

        $quarantined->refresh();
        $this->assertEquals('deleted', $quarantined->status);
    }
}
