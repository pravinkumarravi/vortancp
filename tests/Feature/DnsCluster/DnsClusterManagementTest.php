<?php

declare(strict_types=1);

namespace Tests\Feature\DnsCluster;

use App\Models\User;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\DnsCluster\Livewire\DnsClusterManager;
use App\Modules\DnsCluster\Models\DnsClusterNode;
use App\Modules\DnsCluster\Models\DnsSyncLog;
use App\Modules\DnsCluster\Services\DnsClusterSyncService;
use App\Modules\Domains\Models\Domain;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DnsClusterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Server $server;
    protected \App\Modules\Accounts\Models\HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'Primary NS Master',
            'hostname' => 'ns0.vortancp.local',
            'ip_address' => '127.0.0.1',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $customer = \App\Modules\Accounts\Models\Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'DNS Client',
            'email' => $this->customerUser->email,
            'status' => \App\Modules\Accounts\Models\Customer::STATUS_ACTIVE,
        ]);

        $this->account = \App\Modules\Accounts\Models\HostingAccount::create([
            'customer_id' => $customer->id,
            'server_id' => $this->server->id,
            'username' => 'dnsclient',
            'primary_domain' => 'dnsclient.local',
            'homedir' => '/var/vortan/vhosts/dnsclient',
            'ip_address' => '127.0.0.1',
            'status' => \App\Modules\Accounts\Models\HostingAccount::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_view_dns_cluster_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dns-cluster');

        $response->assertOk()
            ->assertSee('DNS Cluster & Nameserver Fleet', false)
            ->assertSee('Total Nameservers')
            ->assertSee('Add Nameserver Node');
    }

    public function test_customer_is_forbidden_from_dns_cluster(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/admin/dns-cluster');

        $response->assertForbidden();
    }

    public function test_can_register_new_cluster_node_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(DnsClusterManager::class)
            ->set('name', 'US-East Secondary NS')
            ->set('hostname', 'ns1.vortancp.net')
            ->set('ip_address', '198.51.100.10')
            ->set('sync_type', 'slave')
            ->set('api_key', 'vortan_agent_secret_key')
            ->call('addNode')
            ->assertSee('ns1.vortancp.net')
            ->assertSee('US-East Secondary NS');

        $this->assertDatabaseHas('dns_cluster_nodes', [
            'hostname' => 'ns1.vortancp.net',
            'ip_address' => '198.51.100.10',
            'sync_type' => 'slave',
        ]);
    }

    public function test_can_ping_and_sync_node_via_livewire(): void
    {
        $node = DnsClusterNode::create([
            'name' => 'EU Central NS',
            'hostname' => 'ns2.vortancp.net',
            'ip_address' => '198.51.100.20',
            'api_key_hash' => bcrypt('testkey'),
            'sync_type' => 'slave',
            'status' => DnsClusterNode::STATUS_ACTIVE,
        ]);

        $zone = DnsZone::create([
            'hosting_account_id' => $this->account->id,
            'name' => 'vortancp-fleet.com',
            'status' => 'active',
            'serial' => (int) date('Ymd01'),
            'primary_ns' => 'ns1.vortancp.net',
            'admin_email' => 'hostmaster@vortancp-fleet.com',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(DnsClusterManager::class)
            ->call('pingNode', $node->id)
            ->assertSee('ping response');

        Livewire::actingAs($this->adminUser)
            ->test(DnsClusterManager::class)
            ->call('syncNode', $node->id)
            ->assertSee('Synchronized');

        $this->assertDatabaseHas('dns_sync_logs', [
            'dns_cluster_node_id' => $node->id,
            'dns_zone_id' => $zone->id,
            'status' => 'success',
        ]);

        $this->assertGreaterThanOrEqual(1, $node->fresh()->zones_count);
    }

    public function test_can_delete_node(): void
    {
        $node = DnsClusterNode::create([
            'name' => 'Old Deprecated NS',
            'hostname' => 'ns99.vortancp.net',
            'ip_address' => '198.51.100.99',
            'api_key_hash' => bcrypt('testkey'),
            'sync_type' => 'slave',
            'status' => DnsClusterNode::STATUS_OFFLINE,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(DnsClusterManager::class)
            ->call('deleteNode', $node->id);

        $this->assertDatabaseMissing('dns_cluster_nodes', ['id' => $node->id]);
    }

    public function test_service_can_broadcast_zone_change_to_cluster(): void
    {
        DnsClusterNode::create([
            'name' => 'Cluster Node 1',
            'hostname' => 'ns1.cluster.local',
            'ip_address' => '10.0.1.1',
            'api_key_hash' => bcrypt('key1'),
            'sync_type' => 'slave',
            'status' => DnsClusterNode::STATUS_ACTIVE,
        ]);

        DnsClusterNode::create([
            'name' => 'Cluster Node 2',
            'hostname' => 'ns2.cluster.local',
            'ip_address' => '10.0.1.2',
            'api_key_hash' => bcrypt('key2'),
            'sync_type' => 'slave',
            'status' => DnsClusterNode::STATUS_ACTIVE,
        ]);

        $zone = DnsZone::create([
            'hosting_account_id' => $this->account->id,
            'name' => 'broadcast-test.com',
            'status' => 'active',
            'serial' => (int) date('Ymd01'),
            'primary_ns' => 'ns1.cluster.local',
            'admin_email' => 'hostmaster@broadcast-test.com',
        ]);

        $service = app(DnsClusterSyncService::class);
        $result = $service->broadcastZoneChange($zone);

        $this->assertEquals(2, $result['nodes_targeted']);
        $this->assertEquals(2, $result['nodes_synced']);
    }
}
