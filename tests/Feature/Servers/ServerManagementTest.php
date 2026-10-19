<?php

declare(strict_types=1);

namespace Tests\Feature\Servers;

use App\Models\User;
use App\Modules\Identity\Models\Role;
use App\Modules\Servers\Livewire\ServerIndex;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => Role::SUPER_ADMIN], ['label' => 'Super Administrator']);
        Role::firstOrCreate(['name' => Role::CUSTOMER], ['label' => 'Customer']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SUPER_ADMIN);

        $this->customer = User::factory()->create();
        $this->customer->assignRole(Role::CUSTOMER);
    }

    public function test_admin_can_view_server_inventory_page(): void
    {
        Server::create([
            'name' => 'Primary Production Node',
            'hostname' => 'prod01.vortan.local',
            'ip_address' => '10.0.0.5',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get('/admin/servers');
        $response->assertStatus(200);
        $response->assertSee('Primary Production Node');
        $response->assertSee('prod01.vortan.local');
    }

    public function test_customer_is_forbidden_from_server_inventory(): void
    {
        $this->actingAs($this->customer);

        $response = $this->get('/admin/servers');
        $response->assertStatus(403);
    }

    public function test_can_register_new_node_via_livewire(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ServerIndex::class)
            ->set('nodeName', 'US West Node 03')
            ->set('nodeHostname', 'uswest03.vortan.net')
            ->set('nodeIp', '198.51.100.25')
            ->set('nodePort', 8443)
            ->set('nodeConnectionType', 'remote_mtls')
            ->call('registerNode')
            ->assertHasNoErrors()
            ->assertSee('US West Node 03');

        $this->assertDatabaseHas('servers', [
            'name' => 'US West Node 03',
            'hostname' => 'uswest03.vortan.net',
            'connection_type' => 'remote_mtls',
        ]);
    }
}
