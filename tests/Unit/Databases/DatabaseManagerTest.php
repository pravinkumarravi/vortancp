<?php

declare(strict_types=1);

namespace Tests\Unit\Databases;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Databases\Models\Database;
use App\Modules\Databases\Models\DatabaseUser;
use App\Modules\Databases\Services\DatabaseManagerService;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\HostingPlans\Services\EntitlementService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseManagerTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManagerService $dbService;
    private HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbService = new DatabaseManagerService(
            app(EntitlementService::class),
            app(AuditLogger::class)
        );

        $server = Server::create([
            'name' => 'DB Node 01',
            'hostname' => 'db01.vortan.local',
            'ip_address' => '10.0.0.15',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Data User',
            'email' => 'data@acme.com',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'DB Plan',
            'slug' => 'db-plan',
            'disk_quota_mb' => 2000,
            'bandwidth_quota_mb' => 20000,
            'max_domains' => 2,
            'max_subdomains' => 2,
            'max_databases' => 2, // limit is 2
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

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $sub->id,
            'server_id' => $server->id,
            'username' => 'dbclient',
            'primary_domain' => 'dbclient.com',
            'status' => 'active',
            'uid' => 1020,
            'gid' => 1020,
            'homedir' => '/home/dbclient',
        ]);
    }

    public function test_prefixes_database_name_and_enforces_quota(): void
    {
        // 1st database
        $db1 = $this->dbService->createDatabase($this->account, 'shop');
        $this->assertSame('dbclient_shop', $db1->name);

        // 2nd database
        $db2 = $this->dbService->createDatabase($this->account, 'blog');
        $this->assertSame('dbclient_blog', $db2->name);

        // 3rd database should trigger QuotaExceededException (max is 2)
        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessageMatches('/Resource quota exceeded for database/');

        $this->dbService->createDatabase($this->account, 'crm');
    }

    public function test_can_create_user_and_assign_privileges(): void
    {
        $db = $this->dbService->createDatabase($this->account, 'portal');
        $user = $this->dbService->createDatabaseUser($this->account, 'admin', 'SecretPassword123#');

        $this->assertSame('dbclient_admin', $user->username);
        $this->assertSame('localhost', $user->host);

        $assignment = $this->dbService->assignUserToDatabase($db, $user, ['SELECT', 'INSERT', 'UPDATE']);
        $this->assertContains('SELECT', $assignment->privileges);
        $this->assertContains('INSERT', $assignment->privileges);
    }
}
