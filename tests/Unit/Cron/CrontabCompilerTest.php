<?php

declare(strict_types=1);

namespace Tests\Unit\Cron;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Cron\Models\CronJob;
use App\Modules\Cron\Services\CrontabCompilerService;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CrontabCompilerTest extends TestCase
{
    use RefreshDatabase;

    protected CrontabCompilerService $compiler;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new CrontabCompilerService();

        $server = Server::create([
            'name' => 'Cron Master 01',
            'hostname' => 'cron1.vortancp.local',
            'ip_address' => '10.0.0.15',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Cron Plan',
            'slug' => 'cron-plan',
            'disk_quota_mb' => 10240,
            'bandwidth_quota_mb' => 102400,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 10,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Cron Customer',
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
            'username' => 'cronuser',
            'primary_domain' => 'cronuser.test',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1010,
            'gid' => 1010,
            'homedir' => '/home/cronuser',
        ]);
    }

    public function test_validates_valid_cron_schedules(): void
    {
        $this->assertTrue($this->compiler->validateSchedule('*', '*', '*', '*', '*'));
        $this->assertTrue($this->compiler->validateSchedule('*/5', '2', '15', '1-12', '0'));
        $this->assertTrue($this->compiler->validateSchedule('30', '14', '*', '*', '1-5'));
    }

    public function test_rejects_invalid_cron_schedules(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->compiler->validateSchedule('65', '*', '*', '*', '*');
    }

    public function test_sanitizes_and_blocks_dangerous_commands(): void
    {
        $this->assertEquals(
            'php /home/cronuser/public_html/artisan schedule:run',
            $this->compiler->sanitizeCommand('php /home/cronuser/public_html/artisan schedule:run')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Forbidden command pattern detected');
        $this->compiler->sanitizeCommand('sudo rm -rf /');
    }

    public function test_compiles_valid_user_crontab_file(): void
    {
        CronJob::create([
            'hosting_account_id' => $this->account->id,
            'minute' => '*/10',
            'hour' => '*',
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
            'command' => 'php /home/cronuser/public_html/artisan queue:work --stop-when-empty',
            'description' => 'Process background queues',
            'email_notification' => 'alerts@cronuser.test',
            'is_active' => true,
        ]);

        $crontab = $this->compiler->compileUserCrontab($this->account, 'admin@cronuser.test');

        $this->assertStringContainsString('SHELL=/bin/bash', $crontab);
        $this->assertStringContainsString('PATH=/usr/local/bin:/usr/bin:/bin', $crontab);
        $this->assertStringContainsString('HOME=/home/cronuser', $crontab);
        $this->assertStringContainsString('# Process background queues', $crontab);
        $this->assertStringContainsString('*/10 * * * * php /home/cronuser/public_html/artisan queue:work --stop-when-empty', $crontab);
        $this->assertStringContainsString('MAILTO="alerts@cronuser.test"', $crontab);
    }
}
