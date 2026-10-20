<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use InvalidArgumentException;

class AgentDispatcherService
{
    /**
     * Strictly allowlisted system services that can be controlled.
     */
    protected const ALLOWED_SERVICES = [
        'nginx',
        'named',
        'bind9',
        'postfix',
        'dovecot',
        'ufw',
        'fail2ban',
        'php8.1-fpm',
        'php8.2-fpm',
        'php8.3-fpm',
        'php8.4-fpm',
    ];

    /**
     * Strictly allowlisted service actions.
     */
    protected const ALLOWED_ACTIONS = [
        'start',
        'stop',
        'restart',
        'reload',
        'status',
    ];

    /**
     * Dispatch an RPC method call to the appropriate allowlisted handler.
     */
    public function dispatch(string $method, array $params): array
    {
        $this->sanitizeParameters($params);

        return match ($method) {
            'system.service_action' => $this->handleServiceAction($params),
            'nginx.test_config' => $this->handleNginxTest(),
            'dns.check_zone' => $this->handleDnsCheckZone($params),
            'user.create' => $this->handleUserCreate($params),
            'user.delete' => $this->handleUserDelete($params),
            'quota.check' => $this->handleQuotaCheck($params),
            'file.set_permissions' => $this->handleSetPermissions($params),
            default => throw new InvalidArgumentException("Method [{$method}] is not recognized or not allowlisted by vortan-agent.", AgentProtocolService::CODE_METHOD_NOT_FOUND),
        };
    }

    /**
     * Verify no dangerous shell metacharacters exist in any input parameter.
     */
    protected function sanitizeParameters(array $params): void
    {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $this->sanitizeParameters($value);
            } elseif (is_string($value)) {
                if (preg_match('/[;&|`$\n\r\0]/', $value)) {
                    throw new InvalidArgumentException("Parameter [{$key}] contains forbidden shell metacharacters.", AgentProtocolService::CODE_INVALID_PARAMS);
                }
            }
        }
    }

    /**
     * Handle systemd service control.
     */
    protected function handleServiceAction(array $params): array
    {
        $service = strtolower(trim((string) ($params['service'] ?? '')));
        $action = strtolower(trim((string) ($params['action'] ?? '')));

        if (! in_array($service, self::ALLOWED_SERVICES, true)) {
            throw new InvalidArgumentException("Service [{$service}] is not allowed.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        if (! in_array($action, self::ALLOWED_ACTIONS, true)) {
            throw new InvalidArgumentException("Action [{$action}] is not allowed.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        $command = "systemctl {$action} {$service}";
        return $this->executeCommand($command);
    }

    /**
     * Test Nginx configuration validity.
     */
    protected function handleNginxTest(): array
    {
        return $this->executeCommand('nginx -t');
    }

    /**
     * Validate BIND DNS zone syntax.
     */
    protected function handleDnsCheckZone(array $params): array
    {
        $domain = trim((string) ($params['domain'] ?? ''));
        $zoneFile = trim((string) ($params['zone_file'] ?? ''));

        if (! preg_match('/^[a-zA-Z0-9.\-]+$/', $domain)) {
            throw new InvalidArgumentException("Invalid domain parameter for zone verification.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        if (empty($zoneFile) || str_contains($zoneFile, '..')) {
            throw new InvalidArgumentException("Invalid zone file path.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        return $this->executeCommand("named-checkzone {$domain} " . escapeshellarg($zoneFile));
    }

    /**
     * Create isolated Linux system user.
     */
    protected function handleUserCreate(array $params): array
    {
        $username = trim((string) ($params['username'] ?? ''));
        $homedir = trim((string) ($params['homedir'] ?? ''));
        $shell = trim((string) ($params['shell'] ?? '/usr/sbin/nologin'));

        if (! preg_match('/^[a-z0-9_\-]+$/', $username) || strlen($username) > 32) {
            throw new InvalidArgumentException("Invalid system username.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        if (empty($homedir) || str_contains($homedir, '..')) {
            throw new InvalidArgumentException("Invalid home directory.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        $allowedShells = ['/bin/bash', '/bin/sh', '/usr/sbin/nologin', '/bin/false'];
        if (! in_array($shell, $allowedShells, true)) {
            throw new InvalidArgumentException("Shell [{$shell}] is not permitted.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        return $this->executeCommand("useradd -m -d " . escapeshellarg($homedir) . " -s " . escapeshellarg($shell) . " " . escapeshellarg($username));
    }

    /**
     * Delete Linux system user.
     */
    protected function handleUserDelete(array $params): array
    {
        $username = trim((string) ($params['username'] ?? ''));
        $removeHome = (bool) ($params['remove_home'] ?? false);

        if (! preg_match('/^[a-z0-9_\-]+$/', $username)) {
            throw new InvalidArgumentException("Invalid username for deletion.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        $flag = $removeHome ? '-r ' : '';
        return $this->executeCommand("userdel {$flag}" . escapeshellarg($username));
    }

    /**
     * Query user disk quota.
     */
    protected function handleQuotaCheck(array $params): array
    {
        $username = trim((string) ($params['username'] ?? ''));

        if (! preg_match('/^[a-z0-9_\-]+$/', $username)) {
            throw new InvalidArgumentException("Invalid username for quota check.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        return $this->executeCommand("quota -u " . escapeshellarg($username) . " -w");
    }

    /**
     * Set ownership and permissions on chroot/jail paths.
     */
    protected function handleSetPermissions(array $params): array
    {
        $path = trim((string) ($params['path'] ?? ''));
        $user = trim((string) ($params['user'] ?? ''));
        $group = trim((string) ($params['group'] ?? ''));
        $mode = trim((string) ($params['mode'] ?? '0750'));

        if (empty($path) || str_contains($path, '..')) {
            throw new InvalidArgumentException("Invalid path for permissions update.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        if (! preg_match('/^[a-z0-9_\-]+$/', $user) || ! preg_match('/^[a-z0-9_\-]+$/', $group)) {
            throw new InvalidArgumentException("Invalid user or group specification.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        if (! preg_match('/^0?[0-7]{3,4}$/', $mode)) {
            throw new InvalidArgumentException("Invalid octal mode.", AgentProtocolService::CODE_INVALID_PARAMS);
        }

        $chownRes = $this->executeCommand("chown " . escapeshellarg("{$user}:{$group}") . " " . escapeshellarg($path));
        $chmodRes = $this->executeCommand("chmod " . escapeshellarg($mode) . " " . escapeshellarg($path));

        return [
            'exit_code' => $chownRes['exit_code'] === 0 && $chmodRes['exit_code'] === 0 ? 0 : 1,
            'stdout' => trim($chownRes['stdout'] . "\n" . $chmodRes['stdout']),
            'stderr' => trim($chownRes['stderr'] . "\n" . $chmodRes['stderr']),
        ];
    }

    /**
     * Low-level process execution wrapper with mock support for Windows / dev.
     */
    protected function executeCommand(string $command): array
    {
        // When running in non-Linux or development environment without root access, simulate execution
        if (PHP_OS_FAMILY === 'Windows' || getenv('VORTAN_AGENT_MOCK') === 'true') {
            return [
                'exit_code' => 0,
                'stdout' => "[SIMULATED EXECUTION] {$command} completed successfully.",
                'stderr' => '',
                'command' => $command,
            ];
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);
        if (! is_resource($process)) {
            return [
                'exit_code' => 1,
                'stdout' => '',
                'stderr' => "Failed to spawn process for command: {$command}",
                'command' => $command,
            ];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => (string) $stdout,
            'stderr' => (string) $stderr,
            'command' => $command,
        ];
    }
}
