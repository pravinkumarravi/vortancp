<?php

declare(strict_types=1);

namespace Tests\Unit\Agent;

use App\Modules\Agent\Services\AgentProtocolService;
use InvalidArgumentException;
use Tests\TestCase;

class AgentProtocolTest extends TestCase
{
    protected AgentProtocolService $protocol;
    protected string $secret = 'test_cluster_secret_key_1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        $this->protocol = new AgentProtocolService();
    }

    public function test_formats_valid_json_rpc_request_with_hmac(): void
    {
        $request = $this->protocol->formatRequest('system.service_action', ['service' => 'nginx', 'action' => 'reload'], $this->secret, 'req-01');

        $this->assertEquals('2.0', $request['jsonrpc']);
        $this->assertEquals('system.service_action', $request['method']);
        $this->assertEquals('req-01', $request['id']);
        $this->assertArrayHasKey('auth', $request);
        $this->assertArrayHasKey('timestamp', $request['auth']);
        $this->assertArrayHasKey('signature', $request['auth']);
        $this->assertEquals(64, strlen($request['auth']['signature'])); // SHA256 hex is 64 chars
    }

    public function test_validates_correct_request_successfully(): void
    {
        $request = $this->protocol->formatRequest('system.service_action', ['service' => 'nginx'], $this->secret);

        $this->expectNotToPerformAssertions();
        $this->protocol->validateRequest($request, $this->secret);
    }

    public function test_rejects_invalid_signature(): void
    {
        $request = $this->protocol->formatRequest('system.service_action', ['service' => 'nginx'], $this->secret);
        $request['auth']['signature'] = str_repeat('a', 64);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(AgentProtocolService::CODE_AUTH_ERROR);

        $this->protocol->validateRequest($request, $this->secret);
    }

    public function test_rejects_expired_timestamp(): void
    {
        $request = $this->protocol->formatRequest('system.service_action', ['service' => 'nginx'], $this->secret);
        // Emulate 10 minutes ago
        $oldTime = time() - 600;
        $request['auth']['timestamp'] = $oldTime;
        $request['auth']['signature'] = $this->protocol->sign($request['method'], $request['params'], $oldTime, $this->secret);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(AgentProtocolService::CODE_AUTH_ERROR);

        $this->protocol->validateRequest($request, $this->secret, maxDriftSeconds: 60);
    }

    public function test_formats_success_and_error_responses(): void
    {
        $success = $this->protocol->formatSuccessResponse('req-99', ['status' => 'reloaded']);
        $this->assertEquals('2.0', $success['jsonrpc']);
        $this->assertEquals('req-99', $success['id']);
        $this->assertEquals(['status' => 'reloaded'], $success['result']);

        $error = $this->protocol->formatErrorResponse('req-99', AgentProtocolService::CODE_METHOD_NOT_FOUND, 'Method not found');
        $this->assertEquals('2.0', $error['jsonrpc']);
        $this->assertEquals('req-99', $error['id']);
        $this->assertEquals(AgentProtocolService::CODE_METHOD_NOT_FOUND, $error['error']['code']);
    }
}
