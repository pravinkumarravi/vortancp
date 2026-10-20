<?php

declare(strict_types=1);

namespace Tests\Feature\Migration;

use App\Models\User;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Cron\Models\CronJob;
use App\Modules\Databases\Models\Database;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Domains\Models\Domain;
use App\Modules\Email\Models\Mailbox;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Migration\Livewire\MigrationManager;
use App\Modules\Migration\Models\MigrationJob;
use App\Modules\Migration\Services\MigrationExecutionService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class MigrationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected string $testArchivePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        Server::create([
            'name' => 'Migration Target Server',
            'hostname' => 'node1.vortancp.local',
            'ip_address' => '10.0.0.99',
            'status' => Server::STATUS_ACTIVE,
        ]);

        HostingPlan::create([
            'name' => 'Unlimited Plan',
            'slug' => 'unlimited-plan',
            'max_disk_mb' => 50000,
            'max_bandwidth_mb' => 200000,
            'max_domains' => 20,
            'max_subdomains' => 50,
            'max_databases' => 20,
            'max_mailboxes' => 50,
            'max_ftp_accounts' => 10,
            'max_cron_jobs' => 20,
            'has_ssh_access' => true,
            'has_ssl' => true,
            'is_active' => true,
        ]);

        $this->testArchivePath = storage_path('app/testing/cpmove-apexcorp.tar.gz');
        if (!File::exists(dirname($this->testArchivePath))) {
            File::makeDirectory(dirname($this->testArchivePath), 0755, true);
        }
        File::put($this->testArchivePath, 'SIMULATED_CPMOVE_ARCHIVE_DATA');
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testArchivePath)) {
            File::delete($this->testArchivePath);
        }
        parent::tearDown();
    }

    public function test_admin_can_view_migration_manager_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/migration');

        $response->assertOk()
            ->assertSee('Server Migration & Archive Importer', false)
            ->assertSee('Total Migrations')
            ->assertSee('Pre-Flight Inspect');
    }

    public function test_customer_is_forbidden_from_migration_manager(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/admin/migration');

        $response->assertForbidden();
    }

    public function test_can_inspect_archive_metadata_via_preflight(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(MigrationManager::class)
            ->set('archivePath', $this->testArchivePath)
            ->call('inspectArchive')
            ->assertSee('Pre-Flight Inspection Passed: Ready for Import')
            ->assertSee('apexcorp.com')
            ->assertSee('Execute Full Migration');
    }

    public function test_can_execute_full_cpanel_migration_job(): void
    {
        $job = MigrationJob::create([
            'user_id' => $this->adminUser->id,
            'source_type' => 'cpanel',
            'archive_name' => basename($this->testArchivePath),
            'archive_path' => $this->testArchivePath,
            'archive_size_bytes' => File::size($this->testArchivePath),
            'target_username' => 'apexcorp',
            'target_domain' => 'apexcorp.com',
            'status' => MigrationJob::STATUS_PENDING,
            'step_progress' => 0,
            'current_step' => 'Queued',
        ]);

        $executionService = app(MigrationExecutionService::class);
        $completedJob = $executionService->runMigration($job);

        $this->assertEquals(MigrationJob::STATUS_COMPLETED, $completedJob->status);
        $this->assertEquals(100, $completedJob->step_progress);

        // Verify provisioned assets
        $this->assertDatabaseHas('users', ['email' => 'apexcorp@vortan.local']);
        $this->assertDatabaseHas('hosting_accounts', ['username' => 'apexcorp', 'primary_domain' => 'apexcorp.com']);
        $this->assertDatabaseHas('domains', ['domain' => 'apexcorp.com']);
        $this->assertDatabaseHas('dns_zones', ['name' => 'apexcorp.com']);
        $this->assertDatabaseHas('databases', ['name' => 'apexcorp_wp']);
        $this->assertDatabaseHas('mailboxes', ['local_part' => 'info']);
        $this->assertDatabaseHas('cron_jobs', ['is_active' => true]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'migration.job_completed',
        ]);
    }

    public function test_livewire_can_trigger_migration_workflow(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(MigrationManager::class)
            ->set('archivePath', $this->testArchivePath)
            ->call('inspectArchive')
            ->call('startMigration')
            ->assertSee('Completed')
            ->assertSee('apexcorp.com');

        $job = MigrationJob::where('target_username', 'apexcorp')->latest()->first();
        $this->assertNotNull($job);
        $this->assertEquals(MigrationJob::STATUS_COMPLETED, $job->status);
    }
}
