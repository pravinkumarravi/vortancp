<?php

declare(strict_types=1);

namespace Tests\Unit\Backups;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Backups\Models\Backup;
use App\Modules\Backups\Models\BackupSchedule;
use App\Modules\Backups\Services\BackupArchiveService;
use App\Modules\Backups\Services\BackupManagerService;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BackupArchiveService $archiveService;
    protected BackupManagerService $managerService;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $auditLogger = app(AuditLogger::class);
        $this->archiveService = new BackupArchiveService($auditLogger);
        $this->managerService = new BackupManagerService($this->archiveService, $auditLogger);

        $server = Server::create([
            'name' => 'Backup Storage Node 01',
            'hostname' => 'bak01.vortancp.local',
            'ip_address' => '10.0.0.20',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'disk_quota_mb' => 51200,
            'bandwidth_quota_mb' => 512000,
            'max_domains' => 20,
            'max_subdomains' => 50,
            'max_databases' => 20,
            'max_mailboxes' => 50,
            'max_ftp_accounts' => 20,
            'max_cron_jobs' => 20,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Backup Customer',
            'email' => $user->email,
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
            'username' => 'bakuser',
            'primary_domain' => 'bakuser.test',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1015,
            'gid' => 1015,
            'homedir' => '/home/bakuser',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test backup files
        $dir = storage_path("app/backups/{$this->account->username}");
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    public function test_can_generate_backup_snapshot_with_manifest_and_checksum(): void
    {
        $backup = $this->archiveService->generateBackup(
            account: $this->account,
            type: Backup::TYPE_FULL
        );

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals(Backup::STATUS_COMPLETED, $backup->status);
        $this->assertNotEmpty($backup->checksum_sha256);
        $this->assertGreaterThan(0, $backup->size_bytes);

        $filePath = $this->archiveService->getBackupFilePath($backup);
        $this->assertFileExists($filePath);

        $this->assertTrue($this->archiveService->verifyChecksum($backup));
    }

    public function test_can_restore_backup_snapshot(): void
    {
        $backup = $this->archiveService->generateBackup(
            account: $this->account,
            type: Backup::TYPE_FULL
        );

        $result = $this->archiveService->restoreBackup($backup, ['files', 'databases']);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(['files', 'databases'], $result['components_restored']);
    }

    public function test_can_prune_expired_backups(): void
    {
        // Generate 4 backups
        for ($i = 0; $i < 4; $i++) {
            $this->archiveService->generateBackup($this->account, Backup::TYPE_FULL);
        }

        $this->assertEquals(4, $this->account->backups()->count());

        // Keep last 2
        $pruned = $this->managerService->pruneExpiredBackups($this->account, 2);

        $this->assertEquals(2, $pruned);
        $this->assertEquals(2, $this->account->backups()->count());
    }

    public function test_can_create_schedule_and_toggle(): void
    {
        $schedule = $this->managerService->createSchedule(
            account: $this->account,
            frequency: BackupSchedule::FREQ_DAILY,
            retentionCount: 5
        );

        $this->assertEquals('daily', $schedule->frequency);
        $this->assertEquals(5, $schedule->retention_count);
        $this->assertTrue($schedule->is_active);

        $this->managerService->toggleSchedule($schedule);
        $schedule->refresh();
        $this->assertFalse($schedule->is_active);

        $this->managerService->deleteSchedule($schedule);
        $this->assertDatabaseMissing('backup_schedules', ['id' => $schedule->id]);
    }
}
