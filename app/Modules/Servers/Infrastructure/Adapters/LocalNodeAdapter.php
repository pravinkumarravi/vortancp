<?php

declare(strict_types=1);

namespace App\Modules\Servers\Infrastructure\Adapters;

use App\Modules\Servers\Contracts\ServerNodeAdapterInterface;
use App\Modules\Servers\Models\Server;

class LocalNodeAdapter implements ServerNodeAdapterInterface
{
    public function ping(Server $server): bool
    {
        return $server->status !== Server::STATUS_OFFLINE;
    }

    public function fetchTelemetry(Server $server): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? (sys_getloadavg()[0] ?? 0.35) : 0.35;

        return [
            'load_average' => round((float) $loadAvg, 2),
            'cpu_cores' => 4,
            'total_ram_mb' => 8192,
            'used_ram_mb' => 2450,
            'total_disk_gb' => 160,
            'used_disk_gb' => 22,
            'capabilities' => [
                'nginx' => '1.24.0',
                'php-8.1' => '8.1.31',
                'php-8.2' => '8.2.27',
                'php-8.3' => '8.3.16',
                'php-8.4' => '8.4.7',
                'mariadb' => '10.11.6',
                'powerdns' => '4.8.4',
                'postfix' => '3.8.4',
                'dovecot' => '2.3.21',
                'fail2ban' => '1.0.2',
            ],
        ];
    }

    public function executeOperation(Server $server, string $operation, array $parameters): array
    {
        // Allowlisted operations check
        $allowedOperations = [
            'user.create',
            'user.delete',
            'user.modify_quota',
            'web.vhost_apply',
            'web.vhost_remove',
            'php.pool_apply',
            'db.create',
            'db.drop',
            'dns.zone_write',
            'ssl.install',
            'service.action',
        ];

        if (!in_array($operation, $allowedOperations, true)) {
            return [
                'success' => false,
                'exit_code' => 1,
                'stdout' => '',
                'stderr' => "Disallowed operation: {$operation}",
                'error_message' => "Operation '{$operation}' is not in the node allowlist.",
            ];
        }

        return [
            'success' => true,
            'exit_code' => 0,
            'stdout' => "Operation [{$operation}] executed successfully on node [{$server->hostname}].",
            'stderr' => '',
            'error_message' => null,
        ];
    }
}
