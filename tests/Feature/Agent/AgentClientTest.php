<?php

declare(strict_types=1);

namespace Tests\Feature\Agent;

use App\Modules\Agent\Console\Commands\VortanAgentDaemonCommand;
use App\Modules\Agent\Services\AgentClientService;
use App\Modules\Agent\Services\AgentDispatcherService;
use App\Modules\Agent\Services\AgentProtocolService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentClientTest extends TestCase
{
    use RefreshDatabase;

    protected AgentClientService $client;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = app(AgentClientService::class);

        $this->server = Server::create([
            'name' => 'Agent Node 01',
            'hostname' => 'node01.vortancp.local',
            'ip_address' => '10.0.0.60',
            'status' => Server::STATUS_ACTIVE,
            'is_local' => true,
            'auth_token' => 'secret_agent_key_node_01',
        ]);
    }

    public function test_client_reloads_service_via_rpc(): void
    {
        $response = $this->client->reloadService('nginx', $this->server);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals(0, $response['result']['exit_code']);
    }

    public function test_client_tests_nginx_config(): void
    {
        $response = $this->client->testNginx($this->server);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(0, $response['result']['exit_code']);
    }

    public function test_client_checks_dns_zone(): void
    {
        $response = $this->client->checkDnsZone('example.org', '/etc/bind/zones/example.org.zone', $this->server);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(0, $response['result']['exit_code']);
    }

    public function test_client_provisions_and_deletes_system_user(): void
    {
        $createRes = $this->client->createSystemUser('demouser', '/home/demouser', '/bin/bash', $this->server);
        $this->assertEquals(0, $createRes['result']['exit_code']);

        $deleteRes = $this->client->deleteSystemUser('demouser', removeHome: true, server: $this->server);
        $this->assertEquals(0, $deleteRes['result']['exit_code']);
    }

    public function test_daemon_command_processes_line_successfully(): void
    {
        $protocol = app(AgentProtocolService::class);
        $dispatcher = app(AgentDispatcherService::class);
        $command = new VortanAgentDaemonCommand();

        $secret = 'test_secret_123';
        $request = $protocol->formatRequest('system.service_action', ['service' => 'php8.3-fpm', 'action' => 'restart'], $secret, 'id-100');
        $rawLine = json_encode($request);

        $response = $command->processLine($rawLine, $secret, $protocol, $dispatcher);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('id-100', $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals(0, $response['result']['exit_code']);
    }

    public function test_daemon_command_handles_malformed_json_gracefully(): void
    {
        $protocol = app(AgentProtocolService::class);
        $dispatcher = app(AgentDispatcherService::class);
        $command = new VortanAgentDaemonCommand();

        $response = $command->processLine("{ bad json !! }", 'secret', $protocol, $dispatcher);

        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals(AgentProtocolService::CODE_PARSE_ERROR, $response['error']['code']);
    }
}
