<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Analytics\Livewire\DomainAnalytics;
use App\Modules\Analytics\Models\DomainTrafficMetric;
use App\Modules\Analytics\Services\AccessLogParserService;
use App\Modules\Domains\Models\Domain;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Server $server;
    protected HostingAccount $account;
    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'Analytics Web Node',
            'hostname' => 'web1.vortancp.local',
            'ip_address' => '10.0.0.12',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Analytics Client',
            'email' => $this->customerUser->email,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'server_id' => $this->server->id,
            'username' => 'clientanalytics',
            'primary_domain' => 'analyticstest.com',
            'homedir' => '/var/vortan/vhosts/clientanalytics',
            'ip_address' => '10.0.0.12',
            'status' => HostingAccount::STATUS_ACTIVE,
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'server_id' => $this->server->id,
            'domain' => 'analyticstest.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/var/vortan/vhosts/clientanalytics/public_html',
            'status' => Domain::STATUS_ACTIVE,
            'php_version' => '8.3',
        ]);
    }

    public function test_admin_can_view_analytics_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/analytics');

        $response->assertOk()
            ->assertSee('Traffic & Visitor Analytics', false)
            ->assertSee('Unique Visitors')
            ->assertSee('Total HTTP Requests');
    }

    public function test_customer_can_view_own_domain_analytics_page(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/app/analytics');

        $response->assertOk()
            ->assertSee('Traffic & Visitor Analytics', false)
            ->assertSee('analyticstest.com');
    }

    public function test_can_parse_access_log_lines(): void
    {
        $sampleLogs = [
            '203.0.113.10 - - [20/Oct/2026:12:00:00 +0000] "GET / HTTP/1.1" 200 4520 "https://google.com" "Mozilla/5.0"',
            '203.0.113.10 - - [20/Oct/2026:12:01:00 +0000] "GET /about HTTP/1.1" 200 3200 "https://google.com" "Mozilla/5.0"',
            '198.51.100.4 - - [20/Oct/2026:12:02:00 +0000] "GET /missing-page HTTP/1.1" 404 150 "-" "curl/8.5"',
            '192.0.2.8 - - [20/Oct/2026:12:03:00 +0000] "POST /api/submit HTTP/1.1" 500 240 "-" "PostmanRuntime"',
        ];

        $parser = app(AccessLogParserService::class);
        $metric = $parser->parseLogLines($this->domain, $sampleLogs, '2026-10-20');

        $this->assertEquals(4, $metric->total_requests);
        $this->assertEquals(3, $metric->unique_visitors);
        $this->assertEquals(2, $metric->status_2xx);
        $this->assertEquals(1, $metric->status_4xx);
        $this->assertEquals(1, $metric->status_5xx);
        $this->assertArrayHasKey('/', $metric->top_paths);
        $this->assertArrayHasKey('/about', $metric->top_paths);

        $this->assertDatabaseHas('domain_traffic_metrics', [
            'domain_id' => $this->domain->id,
            'total_requests' => 4,
        ]);
    }

    public function test_can_switch_timeframe_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(DomainAnalytics::class)
            ->set('timeframe', 14)
            ->assertSee('Daily Traffic Volume (Last 14 Days)');
    }

    public function test_can_seed_sample_traffic_via_livewire(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(DomainAnalytics::class)
            ->set('selectedDomainId', $this->domain->id)
            ->call('generateSampleTraffic')
            ->assertSee('Synthesized sample traffic metrics');

        $this->assertGreaterThan(0, DomainTrafficMetric::where('domain_id', $this->domain->id)->count());
    }
}
