<?php

declare(strict_types=1);

namespace Tests\Unit\Servers;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Servers\Contracts\ServerNodeAdapterInterface;
use App\Modules\Servers\Infrastructure\Adapters\LocalNodeAdapter;
use App\Modules\Servers\Models\Server;
use App\Modules\Servers\Services\ServerNodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerNodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ServerNodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServerNodeService(
            new LocalNodeAdapter(),
            new AuditLogger()
        );
    }

    public function test_can_register_server_and_ingest_telemetry(): void
    {
        $server = $this->service->registerServer([
            'name' => 'Edge Worker 01',
            'hostname' => 'edge01.vortan.net',
            'ip_address' => '198.51.100.10',
            'port' => 8443,
            'connection_type' => Server::CONNECTION_LOCAL,
            'status' => Server::STATUS_ACTIVE,
        ]);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertEquals('edge01.vortan.net', $server->hostname);
        $this->assertEquals(Server::STATUS_ACTIVE, $server->status);
        $this->assertTrue($server->hasCapability('nginx'));
        $this->assertTrue($server->hasCapability('php-8.4'));
        $this->assertTrue($server->hasCapability('mariadb'));
    }

    public function test_optimal_placement_selects_active_server_with_lowest_load(): void
    {
        $serverHeavy = $this->service->registerServer([
            'name' => 'Heavy Node',
            'hostname' => 'heavy.vortan.net',
            'ip_address' => '198.51.100.11',
        ]);
        $serverHeavy->update(['load_average' => 4.5, 'used_ram_mb' => 7000]);

        $serverLight = $this->service->registerServer([
            'name' => 'Light Node',
            'hostname' => 'light.vortan.net',
            'ip_address' => '198.51.100.12',
        ]);
        $serverLight->update(['load_average' => 0.15, 'used_ram_mb' => 1200]);

        $optimal = $this->service->findOptimalServerForPlacement(['nginx', 'mariadb']);

        $this->assertNotNull($optimal);
        $this->assertEquals('light.vortan.net', $optimal->hostname);
    }

    public function test_rejects_disallowed_command_on_node(): void
    {
        $server = $this->service->registerServer([
            'name' => 'Test Node',
            'hostname' => 'test.vortan.net',
            'ip_address' => '198.51.100.13',
        ]);

        $result = $this->service->executeNodeOperation($server, 'arbitrary.bash_exec', ['cmd' => 'rm -rf /']);

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['exit_code']);
        $this->assertStringContainsString('Disallowed operation', $result['stderr']);
    }
}
