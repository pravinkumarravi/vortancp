<?php

declare(strict_types=1);

namespace Tests\Unit\HostingPlans;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\HostingPlans\Services\EntitlementService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private EntitlementService $entitlements;
    private HostingAccount $account;
    private HostingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entitlements = new EntitlementService();

        $server = Server::create([
            'name' => 'Edge Node 01',
            'hostname' => 'edge01.vortan.local',
            'ip_address' => '10.0.0.10',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);

        $this->plan = HostingPlan::create([
            'name' => 'Starter Hosting',
            'slug' => 'starter',
            'disk_quota_mb' => 2048,
            'bandwidth_quota_mb' => 20480,
            'max_domains' => 2,
            'max_subdomains' => 5,
            'max_databases' => 2,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 2,
            'max_cron_jobs' => 2,
            'has_ssh_access' => false,
            'has_ssl' => true,
            'has_git' => false,
            'has_dns_management' => true,
            'has_wordpress_toolkit' => false,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $this->plan->id,
            'server_id' => $server->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => 'johndoe',
            'primary_domain' => 'johndoe.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1001,
            'gid' => 1001,
            'homedir' => '/home/johndoe',
        ]);

        $subscription->update(['hosting_account_id' => $this->account->id]);
    }

    public function test_allows_resource_creation_within_quota(): void
    {
        // Starter plan allows 2 domains; currentCount 1 is fine
        $this->entitlements->assertCan($this->account, 'domain', 1);
        $this->assertTrue(true);
    }

    public function test_throws_quota_exceeded_exception_when_at_limit(): void
    {
        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessageMatches('/Resource quota exceeded for domain/');

        // Starter plan allows max 2 domains; currentCount 2 should throw
        $this->entitlements->assertCan($this->account, 'domain', 2);
    }

    public function test_suspended_account_cannot_create_resources(): void
    {
        $this->account->update([
            'status' => HostingAccount::STATUS_SUSPENDED,
            'suspended_reason' => 'Overdue payment',
        ]);

        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessageMatches('/Account \[johndoe\] is suspended/');

        $this->entitlements->assertCan($this->account, 'domain', 0);
    }

    public function test_checks_feature_flags_correctly(): void
    {
        $this->assertTrue($this->entitlements->hasFeature($this->account, 'ssl'));
        $this->assertTrue($this->entitlements->hasFeature($this->account, 'dns'));
        $this->assertFalse($this->entitlements->hasFeature($this->account, 'ssh'));
        $this->assertFalse($this->entitlements->hasFeature($this->account, 'git'));
    }
}
