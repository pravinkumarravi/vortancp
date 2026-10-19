<?php

declare(strict_types=1);

namespace Tests\Feature\Cron;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Cron\Livewire\CronIndex;
use App\Modules\Cron\Models\CronJob;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CronManagementTest extends TestCase
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
            'name' => 'Cron Test Node',
            'hostname' => 'crontest.vortancp.local',
            'ip_address' => '10.0.0.18',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Developer Cloud',
            'slug' => 'developer-cloud',
            'disk_quota_mb' => 20480,
            'bandwidth_quota_mb' => 204800,
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
            'name' => 'Acme Scheduler',
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
            'username' => 'scheduler',
            'primary_domain' => 'acmescheduler.test',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1012,
            'gid' => 1012,
            'homedir' => '/home/scheduler',
        ]);
    }

    public function test_admin_and_customer_can_view_cron_manager(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.cron.index'))
            ->assertOk()
            ->assertSeeText('Cron Jobs & Task Scheduler');

        $this->actingAs($this->customerUser)
            ->get(route('customer.cron.index'))
            ->assertOk()
            ->assertSeeText('Cron Jobs & Task Scheduler');
    }

    public function test_can_create_edit_view_raw_and_delete_cron_job_via_livewire(): void
    {
        // 1. Create a cron job
        Livewire::actingAs($this->customerUser)
            ->test(CronIndex::class)
            ->call('applyPreset', 'every_5_minutes')
            ->set('command', 'php /home/scheduler/public_html/artisan backup:clean')
            ->set('description', 'Clean old database backups')
            ->call('createJob')
            ->assertHasNoErrors()
            ->assertSee('php /home/scheduler/public_html/artisan backup:clean');

        $this->assertDatabaseHas('cron_jobs', [
            'hosting_account_id' => $this->account->id,
            'minute' => '*/5',
            'hour' => '*',
            'command' => 'php /home/scheduler/public_html/artisan backup:clean',
            'description' => 'Clean old database backups',
        ]);

        $cronJob = CronJob::where('hosting_account_id', $this->account->id)->first();

        // 2. View Raw Crontab
        Livewire::actingAs($this->customerUser)
            ->test(CronIndex::class)
            ->call('viewRawCrontab')
            ->assertSet('showRawCrontabModal', true)
            ->assertSee('SHELL=/bin/bash')
            ->assertSee('backup:clean');

        // 3. Edit cron job
        Livewire::actingAs($this->customerUser)
            ->test(CronIndex::class)
            ->call('openEditModal', $cronJob->id)
            ->set('minute', '0')
            ->set('hour', '3')
            ->set('command', 'php /home/scheduler/public_html/artisan backup:run')
            ->call('updateJob')
            ->assertHasNoErrors();

        $cronJob->refresh();
        $this->assertEquals('0', $cronJob->minute);
        $this->assertEquals('3', $cronJob->hour);
        $this->assertEquals('php /home/scheduler/public_html/artisan backup:run', $cronJob->command);

        // 4. Toggle Status
        Livewire::actingAs($this->customerUser)
            ->test(CronIndex::class)
            ->call('toggleStatus', $cronJob->id)
            ->assertHasNoErrors();

        $cronJob->refresh();
        $this->assertFalse($cronJob->is_active);

        // 5. Delete Cron Job
        Livewire::actingAs($this->customerUser)
            ->test(CronIndex::class)
            ->call('deleteJob', $cronJob->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('cron_jobs', ['id' => $cronJob->id]);
    }
}
