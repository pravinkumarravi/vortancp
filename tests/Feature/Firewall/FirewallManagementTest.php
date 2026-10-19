<?php

declare(strict_types=1);

namespace Tests\Feature\Firewall;

use App\Models\User;
use App\Modules\Firewall\Livewire\FirewallIndex;
use App\Modules\Firewall\Models\BannedIp;
use App\Modules\Firewall\Models\FirewallRule;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirewallManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'Security Node 01',
            'hostname' => 'sec01.vortancp.local',
            'ip_address' => '10.0.0.8',
            'status' => Server::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_view_firewall_and_customer_is_forbidden(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.firewall.index'))
            ->assertOk()
            ->assertSeeText('Firewall & Port Management');

        $this->actingAs($this->customerUser)
            ->get(route('admin.firewall.index'))
            ->assertForbidden();
    }

    public function test_can_manage_firewall_rules_and_bans_via_livewire(): void
    {
        // 1. Create a custom rule
        Livewire::actingAs($this->adminUser)
            ->test(FirewallIndex::class, ['serverId' => $this->server->id])
            ->set('port', '8080')
            ->set('protocol', 'tcp')
            ->set('action', 'allow')
            ->set('source_ip', '0.0.0.0/0')
            ->set('comment', 'Custom Proxy')
            ->call('createRule')
            ->assertHasNoErrors()
            ->assertSee('8080');

        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '8080',
            'comment' => 'Custom Proxy',
        ]);

        $rule = FirewallRule::where('server_id', $this->server->id)->first();

        // 2. Toggle Rule State
        Livewire::actingAs($this->adminUser)
            ->test(FirewallIndex::class, ['serverId' => $this->server->id])
            ->call('toggleRule', $rule->id)
            ->assertHasNoErrors();

        $rule->refresh();
        $this->assertFalse($rule->is_active);

        // 3. Ban an IP
        Livewire::actingAs($this->adminUser)
            ->test(FirewallIndex::class, ['serverId' => $this->server->id])
            ->set('ban_ip', '198.51.100.99')
            ->set('ban_reason', 'Brute force SSH attack')
            ->set('ban_hours', 48)
            ->call('banIp')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('banned_ips', [
            'server_id' => $this->server->id,
            'ip_address' => '198.51.100.99',
            'reason' => 'Brute force SSH attack',
        ]);

        $banned = BannedIp::where('ip_address', '198.51.100.99')->first();

        // 4. Unban IP
        Livewire::actingAs($this->adminUser)
            ->test(FirewallIndex::class, ['serverId' => $this->server->id])
            ->call('unbanIp', $banned->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('banned_ips', ['id' => $banned->id]);

        // 5. Delete Rule
        Livewire::actingAs($this->adminUser)
            ->test(FirewallIndex::class, ['serverId' => $this->server->id])
            ->call('deleteRule', $rule->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('firewall_rules', ['id' => $rule->id]);
    }
}
