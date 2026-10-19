<?php

declare(strict_types=1);

namespace Tests\Unit\Dns;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Dns\Services\DnsZoneCompilerService;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsZoneCompilerTest extends TestCase
{
    use RefreshDatabase;

    private DnsZoneCompilerService $compiler;
    private DnsZone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new DnsZoneCompilerService();

        $server = Server::create([
            'name' => 'DNS Master',
            'hostname' => 'ns1.vortan.local',
            'ip_address' => '10.0.0.53',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Beta Corp',
            'email' => 'tech@beta.com',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Cloud Tier',
            'slug' => 'cloud',
            'disk_quota_mb' => 10000,
            'bandwidth_quota_mb' => 100000,
            'max_domains' => 5,
            'max_subdomains' => 5,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
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
            'username' => 'betacorp',
            'primary_domain' => 'betacorp.io',
            'status' => 'active',
            'uid' => 1003,
            'gid' => 1003,
            'homedir' => '/home/betacorp',
        ]);

        $this->zone = DnsZone::create([
            'hosting_account_id' => $account->id,
            'name' => 'betacorp.io',
            'status' => 'active',
            'serial' => 2026101901,
            'primary_ns' => 'ns1.vortan.local',
            'admin_email' => 'hostmaster.betacorp.io',
            'refresh_sec' => 10800,
            'retry_sec' => 3600,
            'expire_sec' => 604800,
            'minimum_ttl' => 3600,
        ]);

        $this->zone->records()->createMany([
            ['name' => '@', 'type' => DnsRecord::TYPE_NS, 'content' => 'ns1.vortan.local.', 'ttl' => 86400],
            ['name' => '@', 'type' => DnsRecord::TYPE_A, 'content' => '10.0.0.100', 'ttl' => 3600],
            ['name' => 'www', 'type' => DnsRecord::TYPE_CNAME, 'content' => 'betacorp.io.', 'ttl' => 3600],
            ['name' => '@', 'type' => DnsRecord::TYPE_MX, 'content' => 'mail.betacorp.io.', 'ttl' => 3600, 'priority' => 10],
            ['name' => '@', 'type' => DnsRecord::TYPE_TXT, 'content' => 'v=spf1 a mx ~all', 'ttl' => 3600],
        ]);
    }

    public function test_compiles_rfc_1035_compliant_bind_zone_file(): void
    {
        $zoneFile = $this->compiler->compile($this->zone);

        $this->assertStringContainsString('$ORIGIN betacorp.io.', $zoneFile);
        $this->assertStringContainsString('$TTL 3600', $zoneFile);
        $this->assertStringContainsString('@ IN SOA ns1.vortan.local. hostmaster.betacorp.io.', $zoneFile);
        $this->assertStringContainsString('2026101901', $zoneFile);
        $this->assertStringContainsString('IN NS    ns1.vortan.local.', $zoneFile);
        $this->assertStringContainsString('IN A     10.0.0.100', $zoneFile);
        $this->assertStringContainsString('IN CNAME betacorp.io.', $zoneFile);
        $this->assertStringContainsString('IN MX    10   mail.betacorp.io.', $zoneFile);
        $this->assertStringContainsString('IN TXT   "v=spf1 a mx ~all"', $zoneFile);
    }
}
