<?php

declare(strict_types=1);

namespace Tests\Feature\Metrics;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Metrics\Livewire\MetricsIndex;
use App\Modules\Metrics\Models\AccountMetric;
use App\Modules\Metrics\Models\ServerMetric;
use App\Modules\Metrics\Services\AccountMetricsAggregatorService;
use App\Modules\Metrics\Services\SystemMetricsCollectorService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MetricsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Server $server;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'Metrics Node 01',
            'hostname' => 'node01.vortancp.local',
            'ip_address' => '10.0.0.53',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Metrics Plan',
            'slug' => 'metrics-plan',
            'max_disk_mb' => 2000,
            'max_bandwidth_mb' => 10000,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Metrics Client',
            'email' => $this->customerUser->email,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $this->server->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $this->server->id,
            'username' => 'metricuser',
            'primary_domain' => 'metricsite.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1028,
            'gid' => 1028,
            'homedir' => '/home/metricuser',
        ]);
    }

    public function test_can_render_metrics_index_as_admin_and_customer(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.metrics.index'))
            ->assertOk();

        $this->actingAs($this->customerUser)
            ->get(route('customer.metrics.index'))
            ->assertOk();
    }

    public function test_records_server_metric(): void
    {
        $collector = app(SystemMetricsCollectorService::class);

        $metric = $collector->recordServerMetric($this->server);

        $this->assertInstanceOf(ServerMetric::class, $metric);
        $this->assertEquals($this->server->id, $metric->server_id);
        $this->assertDatabaseHas('server_metrics', [
            'id' => $metric->id,
            'server_id' => $this->server->id,
        ]);
    }

    public function test_aggregates_account_usage_and_flags_warnings(): void
    {
        $aggregator = app(AccountMetricsAggregatorService::class);

        // Record normal usage (200MB out of 2000MB disk = 10%)
        $aggregator->recordAccountMetric($this->account, diskUsedMb: 200, bandwidthUsedMb: 1000, inodesUsed: 500);

        $report = $aggregator->getUsageReport($this->account);
        $this->assertEquals(10.0, $report['disk_pct']);
        $this->assertFalse($report['is_warning']);
        $this->assertFalse($report['is_critical']);

        // Record high usage (1800MB out of 2000MB disk = 90%)
        $aggregator->recordAccountMetric($this->account, diskUsedMb: 1800, bandwidthUsedMb: 1000, inodesUsed: 500);

        $reportHigh = $aggregator->getUsageReport($this->account);
        $this->assertEquals(90.0, $reportHigh['disk_pct']);
        $this->assertTrue($reportHigh['is_warning']);
    }

    public function test_livewire_component_interactivity(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(MetricsIndex::class)
            ->assertSet('selectedServerId', $this->server->id)
            ->call('probeMetrics')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('server_metrics', [
            'server_id' => $this->server->id,
        ]);
    }
}
