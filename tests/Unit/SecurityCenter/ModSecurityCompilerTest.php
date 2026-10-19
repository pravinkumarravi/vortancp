<?php

declare(strict_types=1);

namespace Tests\Unit\SecurityCenter;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\SecurityCenter\Models\WafConfiguration;
use App\Modules\SecurityCenter\Services\ModSecurityCompilerService;
use App\Modules\SecurityCenter\Services\SecurityAdvisorService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModSecurityCompilerTest extends TestCase
{
    use RefreshDatabase;

    protected ModSecurityCompilerService $compiler;
    protected SecurityAdvisorService $advisor;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new ModSecurityCompilerService();
        $this->advisor = new SecurityAdvisorService();

        $this->server = Server::create([
            'name' => 'WAF Gateway 01',
            'hostname' => 'waf01.vortancp.local',
            'ip_address' => '10.0.0.14',
            'status' => Server::STATUS_ACTIVE,
        ]);
    }

    public function test_compiles_enabled_waf_configuration_with_owasp_crs(): void
    {
        $config = WafConfiguration::create([
            'server_id' => $this->server->id,
            'is_enabled' => true,
            'paranoia_level' => 2,
            'block_sqli' => true,
            'block_xss' => true,
            'block_rfi' => false,
            'block_code_injection' => true,
            'anomaly_threshold' => 7,
            'disabled_rule_ids' => [942100, 941100],
            'custom_rules' => 'SecRule ARGS:test "@rx attack" "id:1001,phase:2,deny,status:403"',
        ]);

        $snippet = $this->compiler->compileSnippet($config);

        $this->assertStringContainsString('SecRuleEngine On', $snippet);
        $this->assertStringContainsString('tx.paranoia_level=2', $snippet);
        $this->assertStringContainsString('tx.inbound_anomaly_score_threshold=7', $snippet);
        $this->assertStringContainsString('SecRuleRemoveByTag "attack-rfi"', $snippet);
        $this->assertStringContainsString('SecRuleRemoveById 942100', $snippet);
        $this->assertStringContainsString('SecRuleRemoveById 941100', $snippet);
        $this->assertStringContainsString('SecRule ARGS:test "@rx attack"', $snippet);
    }

    public function test_compiles_disabled_waf(): void
    {
        $config = WafConfiguration::create([
            'server_id' => $this->server->id,
            'is_enabled' => false,
        ]);

        $snippet = $this->compiler->compileSnippet($config);

        $this->assertStringContainsString('SecRuleEngine Off', $snippet);
    }

    public function test_advisor_audits_server_and_calculates_score(): void
    {
        $report = $this->advisor->auditServer($this->server);

        $this->assertArrayHasKey('score', $report);
        $this->assertArrayHasKey('grade', $report);
        $this->assertArrayHasKey('checks', $report);
        $this->assertGreaterThan(0, count($report['checks']));
    }
}
