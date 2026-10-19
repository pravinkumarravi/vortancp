<?php

declare(strict_types=1);

namespace Tests\Unit\Firewall;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Firewall\Models\FirewallRule;
use App\Modules\Firewall\Services\FirewallManagerService;
use App\Modules\Firewall\Services\FirewallRuleCompilerService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FirewallRuleCompilerTest extends TestCase
{
    use RefreshDatabase;

    protected FirewallRuleCompilerService $compiler;
    protected FirewallManagerService $manager;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new FirewallRuleCompilerService();
        $this->manager = new FirewallManagerService($this->compiler, app(AuditLogger::class));

        $this->server = Server::create([
            'name' => 'Edge Gateway 01',
            'hostname' => 'edge01.vortancp.local',
            'ip_address' => '10.0.0.5',
            'status' => Server::STATUS_ACTIVE,
        ]);
    }

    public function test_validates_ports_and_port_ranges(): void
    {
        $this->assertTrue($this->compiler->validatePort('80'));
        $this->assertTrue($this->compiler->validatePort('443'));
        $this->assertTrue($this->compiler->validatePort('20000:20050'));
        $this->assertTrue($this->compiler->validatePort('any'));

        $this->expectException(InvalidArgumentException::class);
        $this->compiler->validatePort('99999');
    }

    public function test_validates_source_ips_and_cidrs(): void
    {
        $this->assertTrue($this->compiler->validateSourceIp('0.0.0.0/0'));
        $this->assertTrue($this->compiler->validateSourceIp('192.168.1.100'));
        $this->assertTrue($this->compiler->validateSourceIp('10.0.0.0/24'));
        $this->assertTrue($this->compiler->validateSourceIp('any'));

        $this->expectException(InvalidArgumentException::class);
        $this->compiler->validateSourceIp('invalid-ip-format');
    }

    public function test_compiles_ufw_and_iptables_scripts(): void
    {
        $this->manager->createRule(
            server: $this->server,
            port: '80',
            protocol: 'tcp',
            action: 'allow',
            sourceIp: '0.0.0.0/0',
            comment: 'HTTP Traffic'
        );

        $this->manager->createRule(
            server: $this->server,
            port: '22',
            protocol: 'tcp',
            action: 'limit',
            sourceIp: '198.51.100.0/24',
            comment: 'SSH Bastion'
        );

        // Test UFW commands
        $ufwCmds = $this->compiler->compileUfwCommands($this->server);
        $this->assertContains('ufw default deny incoming', $ufwCmds);
        $this->assertContains('ufw default allow outgoing', $ufwCmds);
        $this->assertContains("ufw allow proto tcp from any to any port 80 comment 'HTTP Traffic'", $ufwCmds);
        $this->assertContains("ufw limit proto tcp from 198.51.100.0/24 to any port 22 comment 'SSH Bastion'", $ufwCmds);

        // Test IPTables script
        $script = $this->compiler->compileIptablesScript($this->server);
        $this->assertStringContainsString('iptables -P INPUT DROP', $script);
        $this->assertStringContainsString('iptables -A INPUT -p tcp --dport 80 -m comment --comment "HTTP Traffic" -j ACCEPT', $script);
        $this->assertStringContainsString('iptables -A INPUT -p tcp -s 198.51.100.0/24 --dport 22 -m comment --comment "SSH Bastion" -j ACCEPT', $script);
    }

    public function test_applies_standard_hosting_profile(): void
    {
        $added = $this->manager->applyStandardHostingProfile($this->server);

        $this->assertGreaterThan(5, $added);
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '443',
            'protocol' => 'tcp',
            'action' => 'allow',
        ]);
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $this->server->id,
            'port' => '22',
            'protocol' => 'tcp',
            'action' => 'limit',
        ]);
    }
}
