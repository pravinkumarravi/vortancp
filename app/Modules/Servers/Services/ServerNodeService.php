<?php

declare(strict_types=1);

namespace App\Modules\Servers\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Servers\Contracts\ServerNodeAdapterInterface;
use App\Modules\Servers\Models\Server;
use App\Modules\Servers\Models\ServerCapability;
use Illuminate\Support\Collection;

class ServerNodeService
{
    public function __construct(
        protected ServerNodeAdapterInterface $adapter,
        protected AuditLogger $auditLogger
    ) {
    }

    /**
     * Register a new server node and ingest its capabilities.
     *
     * @param array<string, mixed> $data
     */
    public function registerServer(array $data): Server
    {
        $server = Server::create([
            'name' => $data['name'],
            'hostname' => strtolower(trim((string) $data['hostname'])),
            'ip_address' => $data['ip_address'],
            'port' => (int) ($data['port'] ?? 8443),
            'connection_type' => $data['connection_type'] ?? Server::CONNECTION_LOCAL,
            'auth_token' => $data['auth_token'] ?? null,
            'status' => $data['status'] ?? Server::STATUS_ACTIVE,
            'os_name' => $data['os_name'] ?? 'Linux',
            'os_version' => $data['os_version'] ?? 'Ubuntu 24.04 LTS',
            'cpu_cores' => (int) ($data['cpu_cores'] ?? 4),
            'total_ram_mb' => (int) ($data['total_ram_mb'] ?? 8192),
            'total_disk_gb' => (int) ($data['total_disk_gb'] ?? 160),
            'last_heartbeat_at' => now(),
        ]);

        // Sync initial telemetry and capabilities
        $this->syncServerHealth($server);

        $this->auditLogger->log(
            event: 'server.registered',
            auditable: $server,
            newValues: [
                'name' => $server->name,
                'hostname' => $server->hostname,
                'ip_address' => $server->ip_address,
            ]
        );

        return $server;
    }

    /**
     * Ping node and update telemetry in database.
     */
    public function syncServerHealth(Server $server): void
    {
        $isOnline = $this->adapter->ping($server);

        if (!$isOnline) {
            $server->update(['status' => Server::STATUS_OFFLINE]);
            return;
        }

        $telemetry = $this->adapter->fetchTelemetry($server);

        $server->update([
            'status' => Server::STATUS_ACTIVE,
            'load_average' => $telemetry['load_average'],
            'cpu_cores' => $telemetry['cpu_cores'],
            'total_ram_mb' => $telemetry['total_ram_mb'],
            'used_ram_mb' => $telemetry['used_ram_mb'],
            'total_disk_gb' => $telemetry['total_disk_gb'],
            'used_disk_gb' => $telemetry['used_disk_gb'],
            'last_heartbeat_at' => now(),
        ]);

        // Upsert capabilities
        if (!empty($telemetry['capabilities'])) {
            foreach ($telemetry['capabilities'] as $capability => $version) {
                ServerCapability::updateOrCreate(
                    [
                        'server_id' => $server->id,
                        'capability' => $capability,
                    ],
                    [
                        'version' => $version,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Select the best server node for placing a new hosting account or website.
     *
     * @param array<string> $requiredCapabilities
     */
    public function findOptimalServerForPlacement(array $requiredCapabilities = []): ?Server
    {
        $query = Server::query()->where('status', Server::STATUS_ACTIVE);

        foreach ($requiredCapabilities as $capability) {
            $query->whereHas('capabilities', function ($q) use ($capability) {
                $q->where('capability', $capability)->where('is_active', true);
            });
        }

        // Rank by lowest load average and lowest RAM percentage
        return $query->orderBy('load_average', 'asc')
            ->orderBy('used_ram_mb', 'asc')
            ->first();
    }

    /**
     * Execute an allowlisted operational command on the remote node.
     *
     * @param array<string, mixed> $parameters
     * @return array{success: bool, exit_code: int, stdout: string, stderr: string, error_message: string|null}
     */
    public function executeNodeOperation(Server $server, string $operation, array $parameters): array
    {
        $result = $this->adapter->executeOperation($server, $operation, $parameters);

        $this->auditLogger->log(
            event: "node.operation.{$operation}",
            auditable: $server,
            newValues: [
                'operation' => $operation,
                'parameters' => $parameters,
                'success' => $result['success'],
                'exit_code' => $result['exit_code'],
            ]
        );

        return $result;
    }
}
