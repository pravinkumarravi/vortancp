<?php

declare(strict_types=1);

namespace App\Modules\Servers\Contracts;

use App\Modules\Servers\Models\Server;

interface ServerNodeAdapterInterface
{
    /**
     * Test connectivity to the node agent.
     */
    public function ping(Server $server): bool;

    /**
     * Retrieve hardware, OS, and daemon telemetry metrics from the server.
     *
     * @return array{
     *     load_average: float,
     *     cpu_cores: int,
     *     total_ram_mb: int,
     *     used_ram_mb: int,
     *     total_disk_gb: int,
     *     used_disk_gb: int,
     *     capabilities: array<string, string|null>
     * }
     */
    public function fetchTelemetry(Server $server): array;

    /**
     * Execute an allowlisted operation on the node agent.
     *
     * @param array<string, mixed> $parameters
     * @return array{
     *     success: bool,
     *     exit_code: int,
     *     stdout: string,
     *     stderr: string,
     *     error_message: string|null
     * }
     */
    public function executeOperation(Server $server, string $operation, array $parameters): array;
}
