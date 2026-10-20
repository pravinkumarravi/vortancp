<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console\Commands;

use App\Modules\Agent\Services\AgentDispatcherService;
use App\Modules\Agent\Services\AgentProtocolService;
use Illuminate\Console\Command;
use Throwable;

class VortanAgentDaemonCommand extends Command
{
    protected $signature = 'vortan:agent 
                            {--socket=/run/vortan/agent.sock : Unix domain socket path} 
                            {--tcp : Listen on TCP stream socket instead of Unix socket} 
                            {--port=8443 : TCP port to listen on when in TCP mode} 
                            {--host=0.0.0.0 : Bind address for TCP mode} 
                            {--once : Handle a single connection and exit (for diagnostics)}';

    protected $description = 'Start the VortanCP privileged agent daemon for root-level host orchestration';

    public function handle(AgentProtocolService $protocol, AgentDispatcherService $dispatcher): int
    {
        $secret = config('vortan.agent.secret', 'default_vortan_agent_secret_key_32bytes');
        $useTcp = $this->option('tcp');
        $once = $this->option('once');

        if ($useTcp) {
            $host = $this->option('host');
            $port = (int) $this->option('port');
            $bindAddress = "tcp://{$host}:{$port}";
        } else {
            $socketPath = $this->option('socket');
            $bindAddress = "unix://{$socketPath}";

            $dir = dirname($socketPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (file_exists($socketPath)) {
                @unlink($socketPath);
            }
        }

        $this->info("Starting VortanCP Agent Daemon listening on [{$bindAddress}]...");

        $serverSocket = @stream_socket_server($bindAddress, $errno, $errstr);
        if (! $serverSocket) {
            $this->error("Failed to bind socket on [{$bindAddress}]: {$errstr} ({$errno})");
            return 1;
        }

        if (! $useTcp && isset($socketPath)) {
            @chmod($socketPath, 0660);
        }

        $this->info("VortanCP Agent Daemon ready. Waiting for JSON-RPC connections.");

        while (true) {
            $client = @stream_socket_accept($serverSocket, -1);
            if (! $client) {
                continue;
            }

            $rawInput = fgets($client);
            if ($rawInput !== false && trim($rawInput) !== '') {
                $response = $this->processLine(trim($rawInput), $secret, $protocol, $dispatcher);
                fwrite($client, json_encode($response) . "\n");
            }

            fclose($client);

            if ($once) {
                $this->info("Completed single connection (--once). Stopping daemon.");
                break;
            }
        }

        fclose($serverSocket);
        if (! $useTcp && isset($socketPath) && file_exists($socketPath)) {
            @unlink($socketPath);
        }

        return 0;
    }

    /**
     * Parse and process a single JSON-RPC line.
     */
    public function processLine(
        string $line,
        string $secret,
        AgentProtocolService $protocol,
        AgentDispatcherService $dispatcher
    ): array {
        $data = json_decode($line, true);
        $id = is_array($data) ? ($data['id'] ?? null) : null;

        if (! is_array($data)) {
            return $protocol->formatErrorResponse($id, AgentProtocolService::CODE_PARSE_ERROR, "Invalid JSON payload.");
        }

        try {
            $protocol->validateRequest($data, $secret);
            $result = $dispatcher->dispatch($data['method'], $data['params'] ?? []);
            return $protocol->formatSuccessResponse($id, $result);
        } catch (Throwable $e) {
            $code = $e->getCode() ?: AgentProtocolService::CODE_INTERNAL_ERROR;
            return $protocol->formatErrorResponse($id, (int) $code, $e->getMessage());
        }
    }
}
