<?php

declare(strict_types=1);

namespace Tests\Feature\Backups;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Backups\Livewire\BackupIndex;
use App\Modules\Backups\Models\Backup;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'Backup Test Server',
            'hostname' => 'baktest.vortancp.local',
            'ip_address' => '10.0.0.22',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Unlimited Plan',
            'slug' => 'unlimited-plan',
            'disk_quota_mb' => 50000,
            'bandwidth_quota_mb' => 500000,
            'max_domains' => 10,
            'max_subdomains' => 20,
            'max_databases' => 10,
            'max_mailboxes' => 20,
            'max_ftp_accounts' => 10,
            'max_cron_jobs' => 10,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Apex Backups',
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
            'username' => 'apexbackup',
            'primary_domain' => 'apexbackup.test',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1018,
            'gid' => 1018,
            'homedir' => '/home/apexbackup',
        ]);
    }

    protected function tearDown(): void
    {
        $dir = storage_path("app/backups/{$this->account->username}");
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    public function test_admin_and_customer_can_view_backups_page(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSeeText('Backup & Disaster Recovery');

        $this->actingAs($this->customerUser)
            ->get(route('customer.backups.index'))
            ->assertOk()
            ->assertSeeText('Backup & Disaster Recovery');
    }

    public function test_can_generate_backup_and_inspect_manifest_via_livewire(): void
    {
        // 1. Generate Backup
        Livewire::actingAs($this->customerUser)
            ->test(BackupIndex::class)
            ->set('backup_type', 'full')
            ->call('generateBackup')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('backups', [
            'hosting_account_id' => $this->account->id,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $backup = Backup::where('hosting_account_id', $this->account->id)->first();

        // 2. View Manifest
        Livewire::actingAs($this->customerUser)
            ->test(BackupIndex::class)
            ->call('viewManifest', $backup->id)
            ->assertSet('showManifestModal', true)
            ->assertSee('apexbackup.test');

        // 3. Restore Backup
        Livewire::actingAs($this->customerUser)
            ->test(BackupIndex::class)
            ->call('openRestoreModal', $backup->id)
            ->set('restore_files', true)
            ->set('restore_databases', true)
            ->call('performRestore')
            ->assertHasNoErrors();

        // 4. Create Schedule Policy
        Livewire::actingAs($this->customerUser)
            ->test(BackupIndex::class)
            ->set('sched_frequency', 'weekly')
            ->set('sched_retention', 4)
            ->call('createSchedule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('backup_schedules', [
            'hosting_account_id' => $this->account->id,
            'frequency' => 'weekly',
            'retention_count' => 4,
        ]);

        // 5. Delete Backup
        Livewire::actingAs($this->customerUser)
            ->test(BackupIndex::class)
            ->call('deleteBackup', $backup->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }
}
