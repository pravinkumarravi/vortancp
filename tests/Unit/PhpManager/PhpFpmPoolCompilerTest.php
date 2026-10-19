<?php

declare(strict_types=1);

namespace Tests\Unit\PhpManager;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\PhpManager\Models\PhpPool;
use App\Modules\PhpManager\Services\PhpFpmPoolCompilerService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpFpmPoolCompilerTest extends TestCase
{
    use RefreshDatabase;

    private PhpFpmPoolCompilerService $compiler;
    private PhpPool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new PhpFpmPoolCompilerService();

        $server = Server::create([
            'name' => 'FPM Node 01',
            'hostname' => 'fpm01.vortan.local',
            'ip_address' => '10.0.0.18',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'name' => 'PHP Dev',
            'email' => 'dev@acme.com',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'PHP Tier',
            'slug' => 'php-tier',
            'disk_quota_mb' => 2000,
            'bandwidth_quota_mb' => 20000,
            'max_domains' => 2,
            'max_subdomains' => 2,
            'max_databases' => 2,
            'max_mailboxes' => 2,
            'max_ftp_accounts' => 2,
            'max_cron_jobs' => 2,
            'has_ssh_access' => false,
            'has_ssl' => true,
            'has_git' => false,
            'has_dns_management' => true,
            'has_wordpress_toolkit' => false,
            'is_active' => true,
        ]);

        $sub = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $server->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $sub->id,
            'server_id' => $server->id,
            'username' => 'phpuser',
            'primary_domain' => 'phptest.org',
            'status' => 'active',
            'uid' => 1040,
            'gid' => 1040,
            'homedir' => '/home/phpuser',
        ]);

        $this->pool = PhpPool::create([
            'hosting_account_id' => $account->id,
            'version' => '8.4',
            'socket_path' => '/run/php/php8.4-fpm-phpuser.sock',
            'pm_type' => 'ondemand',
            'pm_max_children' => 15,
            'memory_limit' => '512M',
            'max_execution_time' => 120,
            'upload_max_filesize' => '128M',
            'post_max_size' => '128M',
            'display_errors' => false,
        ]);
    }

    public function test_compiles_hardened_fpm_pool_configuration(): void
    {
        $config = $this->compiler->compile($this->pool);

        $this->assertStringContainsString('[phpuser]', $config);
        $this->assertStringContainsString('user = phpuser', $config);
        $this->assertStringContainsString('group = phpuser', $config);
        $this->assertStringContainsString('listen = /run/php/php8.4-fpm-phpuser.sock', $config);
        $this->assertStringContainsString('pm = ondemand', $config);
        $this->assertStringContainsString('pm.max_children = 15', $config);
        $this->assertStringContainsString('php_admin_value[open_basedir] = /home/phpuser:/tmp:/var/tmp', $config);
        $this->assertStringContainsString('php_admin_value[memory_limit] = 512M', $config);
        $this->assertStringContainsString('php_admin_value[max_execution_time] = 120', $config);
        $this->assertStringContainsString('php_admin_flag[display_errors] = off', $config);
    }
}
