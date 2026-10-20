<?php

declare(strict_types=1);

namespace Tests\Unit\Agent;

use App\Modules\Agent\Services\AgentDispatcherService;
use App\Modules\Agent\Services\AgentProtocolService;
use InvalidArgumentException;
use Tests\TestCase;

class AgentDispatcherTest extends TestCase
{
    protected AgentDispatcherService $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new AgentDispatcherService();
    }

    public function test_dispatches_allowlisted_service_reload(): void
    {
        $result = $this->dispatcher->dispatch('system.service_action', [
            'service' => 'nginx',
            'action' => 'reload',
        ]);

        $this->assertEquals(0, $result['exit_code']);
        $this->assertStringContainsString('nginx', $result['command']);
    }

    public function test_rejects_unauthorized_service(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(AgentProtocolService::CODE_INVALID_PARAMS);

        $this->dispatcher->dispatch('system.service_action', [
            'service' => 'apache2',
            'action' => 'reload',
        ]);
    }

    public function test_rejects_unauthorized_action(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(AgentProtocolService::CODE_INVALID_PARAMS);

        $this->dispatcher->dispatch('system.service_action', [
            'service' => 'nginx',
            'action' => 'mask',
        ]);
    }

    public function test_rejects_shell_injection_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(AgentProtocolService::CODE_INVALID_PARAMS);

        $this->dispatcher->dispatch('system.service_action', [
            'service' => 'nginx; rm -rf /',
            'action' => 'reload',
        ]);
    }

    public function test_dispatches_nginx_test_config(): void
    {
        $result = $this->dispatcher->dispatch('nginx.test_config', []);

        $this->assertEquals(0, $result['exit_code']);
        $this->assertStringContainsString('nginx -t', $result['command']);
    }

    public function test_dispatches_dns_check_zone(): void
    {
        $result = $this->dispatcher->dispatch('dns.check_zone', [
            'domain' => 'example.com',
            'zone_file' => '/etc/bind/zones/example.com.zone',
        ]);

        $this->assertEquals(0, $result['exit_code']);
        $this->assertStringContainsString('named-checkzone', $result['command']);
    }

    public function test_dispatches_user_management(): void
    {
        $createRes = $this->dispatcher->dispatch('user.create', [
            'username' => 'testuser',
            'homedir' => '/home/testuser',
            'shell' => '/bin/bash',
        ]);
        $this->assertEquals(0, $createRes['exit_code']);
        $this->assertStringContainsString('useradd', $createRes['command']);

        $deleteRes = $this->dispatcher->dispatch('user.delete', [
            'username' => 'testuser',
            'remove_home' => true,
        ]);
        $this->assertEquals(0, $deleteRes['exit_code']);
        $this->assertStringContainsString('userdel -r', $deleteRes['command']);
    }

    public function test_rejects_unknown_method(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(AgentProtocolService::CODE_METHOD_NOT_FOUND);

        $this->dispatcher->dispatch('system.arbitrary_exec', ['cmd' => 'whoami']);
    }
}
