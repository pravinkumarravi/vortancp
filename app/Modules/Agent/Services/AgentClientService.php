<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Modules\Servers\Models\Server;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class AgentClientService
{
    protected string $secret;
    protected string $socketPath;
    protected string $driver;

    public function __construct(
        protected AgentProtocolService $protocol,
        protected AgentDispatcherService $dispatcher
    ) {
        $this->secret = config('vortan.agent.secret', 'default_vortan_agent_secret_key_32bytes');
        $this->socketPath = config('vortan.agent.socket', '/run/vortan/agent.sock');
        $this->driver = config('vortan.agent.driver', 'auto'); // auto, socket, direct
    }

    /**
     * Dispatch an allowlisted RPC call to the target node agent.
     */
    public function call(string $method, array $params = [], ?Server $server = null): array
    {
        $secret = $server?->auth_token ?? $this->secret;
        $requestPayload = $this->protocol->formatRequest($method, $params, $secret);

        // Decide dispatch strategy
        if ($this->shouldUseDirectDispatch($server)) {
            // Direct in-process verification & execution
            $this->protocol->validateRequest($requestPayload, $secret);
            $result = $this->dispatcher->dispatch($method, $params);
            return $this->protocol->formatSuccessResponse($requestPayload['id'], $result);
        }

        return $this->dispatchOverSocket($requestPayload, $server);
    }

    /**
     * Helper to reload system services (nginx, php-fpm, named, etc.).
     */
    public function reloadService(string $service, ?Server $server = null): array
    {
        return $this->call('system.service_action', ['service' => $service, 'action' => 'reload'], $server);
    }

    /**
     * Helper to restart system services.
     */
    public function restartService(string $service, ?Server $server = null): array
    {
        return $this->call('system.service_action', ['service' => $service, 'action' => 'restart'], $server);
    }

    /**
     * Helper to test Nginx configuration.
     */
    public function testNginx(?Server $server = null): array
    {
        return $this->call('nginx.test_config', [], $server);
    }

    /**
     * Helper to check DNS zone file syntax.
     */
    public function checkDnsZone(string $domain, string $zoneFile, ?Server $server = null): array
    {
        return $this->call('dns.check_zone', ['domain' => $domain, 'zone_file' => $zoneFile], $server);
    }

    /**
     * Helper to provision Linux system user.
     */
    public function createSystemUser(string $username, string $homedir, string $shell = '/usr/sbin/nologin', ?Server $server = null): array
    {
        return $this->call('user.create', [
            'username' => $username,
            'homedir' => $homedir,
            'shell' => $shell,
        ], $server);
    }

    /**
     * Helper to delete Linux system user.
     */
    public function deleteSystemUser(string $username, bool $removeHome = false, ?Server $server = null): array
    {
        return $this->call('user.delete', [
            'username' => $username,
            'remove_home' => $removeHome,
        ], $server);
    }

    /**
     * Determine whether to use direct in-process dispatcher (e.g. testing, Windows dev, or driver configured as direct).
     */
    protected function shouldUseDirectDispatch(?Server $server = null): bool
    {
        if ($this->driver === 'direct') {
            return true;
        }

        if (PHP_OS_FAMILY === 'Windows' || app()->environment('testing')) {
            return true;
        }

        if ($server && ! $server->isLocal()) {
            return false;
        }

        return ! file_exists($this->socketPath);
    }

    /**
     * Dispatch JSON-RPC payload over Unix domain socket or TCP socket to remote node.
     */
    protected function dispatchOverSocket(array $payload, ?Server $server = null): array
    {
        $address = ($server && ! $server->isLocal())
            ? "tcp://{$server->ip_address}:8443"
            : "unix://{$this->socketPath}";

        $fp = @stream_socket_client($address, $errno, $errstr, 5);
        if (! $fp) {
            throw new RuntimeException("Could not connect to vortan-agent at [{$address}]: {$errstr} ({$errno})");
        }

        $json = json_encode($payload) . "\n";
        fwrite($fp, $json);

        $responseLine = fgets($fp);
        fclose($fp);

        if ($responseLine === false) {
            throw new RuntimeException("Empty response received from vortan-agent at [{$address}].");
        }

        $response = json_decode($responseLine, true);
        if (! is_array($response)) {
            throw new RuntimeException("Malformed JSON response from vortan-agent.");
        }

        if (isset($response['error'])) {
            throw new RuntimeException("Agent RPC Error [{$response['error']['code']}]: {$response['error']['message']}");
        }

        return $response;
    }
}
