<?php

declare(strict_types=1);

namespace App\Modules\Terminal\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\HostingPlans\Services\EntitlementService;
use App\Modules\Identity\Models\Role;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class TerminalExecutionService
{
    /**
     * Prohibited destructive commands or privilege escalation attempts for customers.
     */
    protected const BLOCKED_CUSTOMER_PATTERNS = [
        '/\bsudo\b/i',
        '/\bsu\b/i',
        '/\brm\s+-(?:r[fF]|f[rR]|rf|fr)\s+[\/\~]/i',
        '/\bmkfs\b/i',
        '/\bdd\s+if=/i',
        '/:\(\)\s*\{\s*:\|:&\s*\};:/i', // Fork bomb
        '/\bshutdown\b/i',
        '/\breboot\b/i',
        '/\binit\s+[0-6]/i',
        '/\bpoweroff\b/i',
        '/\bhalt\b/i',
        '/\bsystemctl\s+(?:stop|disable|restart|mask)/i',
        '/\bservice\s+\S+\s+(?:stop|disable|restart)/i',
        '/\bufw\s+disable/i',
        '/\biptables\s+-F/i',
        '/\bkill\s+-9\s+1\b/i',
        '/\bchmod\s+(?:-R\s+)?777\s+\//i',
        '/\bchown\s+(?:-R\s+)?root/i',
    ];

    public function __construct(
        protected AuditLogger $auditLogger,
        protected EntitlementService $entitlementService
    ) {}

    /**
     * Check if user is entitled to use the terminal.
     */
    public function isUserEntitled(User $user): bool
    {
        if ($user->hasRole([Role::SUPER_ADMIN, Role::SERVER_ADMIN])) {
            return true;
        }

        if ($user->hasRole(Role::CUSTOMER)) {
            $account = $user->customer?->hostingAccounts()->first();
            if ($account) {
                return $this->entitlementService->hasFeature($account, 'ssh');
            }

            $plan = $user->customer?->subscriptions()->where('status', 'active')->first()?->plan;
            if ($plan) {
                return (bool) $plan->has_ssh_access;
            }

            return false;
        }

        return false;
    }

    /**
     * Get the default working directory for a given user.
     */
    public function getDefaultWorkingDirectory(User $user): string
    {
        if ($user->hasRole([Role::SUPER_ADMIN, Role::SERVER_ADMIN])) {
            return base_path();
        }

        $account = $user->customer?->hostingAccounts()->first();
        $username = $account?->username ?? explode('@', $user->email)[0];
        $vhostPath = storage_path("app/vhosts/{$username}");

        if (!File::exists($vhostPath)) {
            File::makeDirectory($vhostPath, 0755, true, true);
        }

        return $vhostPath;
    }

    /**
     * Get the customer jail root.
     */
    public function getCustomerJailPath(User $user): string
    {
        $account = $user->customer?->hostingAccounts()->first();
        $username = $account?->username ?? explode('@', $user->email)[0];
        return storage_path("app/vhosts/{$username}");
    }

    /**
     * Execute a terminal command.
     *
     * @return array{
     *     command: string,
     *     output: string,
     *     exit_code: int,
     *     cwd: string,
     *     duration_ms: float,
     *     is_directory_change: bool
     * }
     */
    public function execute(User $user, string $command, string $cwd): array
    {
        $startTime = microtime(true);
        $command = trim($command);

        if (!$this->isUserEntitled($user)) {
            return [
                'command' => $command,
                'output' => "Access Denied: In-Browser Web Terminal access is disabled on your current hosting plan. Please upgrade your package to enable SSH & terminal capabilities.\n",
                'exit_code' => 126,
                'cwd' => $cwd,
                'duration_ms' => 0.0,
                'is_directory_change' => false,
            ];
        }

        if ($command === '') {
            return [
                'command' => '',
                'output' => '',
                'exit_code' => 0,
                'cwd' => $cwd,
                'duration_ms' => 0.0,
                'is_directory_change' => false,
            ];
        }

        $isCustomer = $user->hasRole(Role::CUSTOMER);
        $jailPath = $this->getCustomerJailPath($user);

        // Ensure current CWD is within bounds for customer
        if ($isCustomer && !$this->isWithinJail($cwd, $jailPath)) {
            $cwd = $this->getDefaultWorkingDirectory($user);
        }

        // Check for customer security violations
        if ($isCustomer) {
            foreach (self::BLOCKED_CUSTOMER_PATTERNS as $pattern) {
                if (preg_match($pattern, $command)) {
                    $duration = round((microtime(true) - $startTime) * 1000, 2);
                    $this->auditLogger->log(
                        event: 'terminal.blocked_command',
                        auditable: null,
                        oldValues: null,
                        newValues: [
                            'command' => $command,
                            'reason' => 'Prohibited command execution attempt',
                            'cwd' => $cwd,
                        ],
                        userId: $user->id
                    );

                    return [
                        'command' => $command,
                        'output' => "vortan-pty: security violation: command execution blocked by system security policy.\n",
                        'exit_code' => 1,
                        'cwd' => $cwd,
                        'duration_ms' => $duration,
                        'is_directory_change' => false,
                    ];
                }
            }
        }

        // Handle internal command: 'cd'
        if (preg_match('/^cd(?:\s+(.*))?$/', $command, $matches)) {
            $targetDir = trim($matches[1] ?? '');
            return $this->handleDirectoryChange($user, $targetDir, $cwd, $jailPath, $isCustomer, $startTime, $command);
        }

        // Handle internal command: 'pwd'
        if ($command === 'pwd') {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $cleanCwd = str_replace('\\', '/', $cwd);

            $this->auditLogger->log(
                event: 'terminal.command_executed',
                auditable: null,
                oldValues: null,
                newValues: [
                    'command' => $command,
                    'cwd' => $cwd,
                    'exit_code' => 0,
                    'duration_ms' => $duration,
                ],
                userId: $user->id
            );

            return [
                'command' => $command,
                'output' => $cleanCwd . "\n",
                'exit_code' => 0,
                'cwd' => $cwd,
                'duration_ms' => $duration,
                'is_directory_change' => false,
            ];
        }

        // Handle internal command: 'clear'
        if ($command === 'clear') {
            return [
                'command' => $command,
                'output' => '__CLEAR_SCREEN__',
                'exit_code' => 0,
                'cwd' => $cwd,
                'duration_ms' => 0.0,
                'is_directory_change' => false,
            ];
        }

        // Run process via Symfony Process
        try {
            $process = Process::fromShellCommandline($command, $cwd, null, null, 15);
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode() ?? 0;

            $fullOutput = $output;
            if ($errorOutput !== '') {
                $fullOutput .= ($fullOutput !== '' ? "\n" : '') . $errorOutput;
            }

            if ($fullOutput === '' && $exitCode === 0) {
                $fullOutput = "(Command executed successfully with no output)\n";
            }
        } catch (\Throwable $e) {
            $fullOutput = "vortan-pty: error: " . $e->getMessage() . "\n";
            $exitCode = 1;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Audit log command execution
        $this->auditLogger->log(
            event: 'terminal.command_executed',
            auditable: null,
            oldValues: null,
            newValues: [
                'command' => $command,
                'cwd' => $cwd,
                'exit_code' => $exitCode,
                'duration_ms' => $duration,
            ],
            userId: $user->id
        );

        return [
            'command' => $command,
            'output' => $fullOutput,
            'exit_code' => $exitCode,
            'cwd' => $cwd,
            'duration_ms' => $duration,
            'is_directory_change' => false,
        ];
    }

    /**
     * Handle directory change command (cd).
     */
    protected function handleDirectoryChange(
        User $user,
        string $targetDir,
        string $cwd,
        string $jailPath,
        bool $isCustomer,
        float $startTime,
        string $command
    ): array {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($targetDir === '' || $targetDir === '~') {
            $newCwd = $this->getDefaultWorkingDirectory($user);
            return [
                'command' => $command,
                'output' => '',
                'exit_code' => 0,
                'cwd' => $newCwd,
                'duration_ms' => $duration,
                'is_directory_change' => true,
            ];
        }

        // Resolve absolute or relative path
        if (str_starts_with($targetDir, '/') || (strlen($targetDir) > 2 && $targetDir[1] === ':')) {
            $resolvedPath = realpath($targetDir);
            if ($resolvedPath === false) {
                return [
                    'command' => $command,
                    'output' => "cd: no such file or directory: {$targetDir}\n",
                    'exit_code' => 1,
                    'cwd' => $cwd,
                    'duration_ms' => $duration,
                    'is_directory_change' => false,
                ];
            }
        } else {
            $combined = $cwd . DIRECTORY_SEPARATOR . $targetDir;
            $resolvedPath = realpath($combined);
            if ($resolvedPath === false) {
                return [
                    'command' => $command,
                    'output' => "cd: no such file or directory: {$targetDir}\n",
                    'exit_code' => 1,
                    'cwd' => $cwd,
                    'duration_ms' => $duration,
                    'is_directory_change' => false,
                ];
            }
        }

        // Validate customer jail path traversal
        if ($isCustomer && !$this->isWithinJail($resolvedPath, $jailPath)) {
            $this->auditLogger->log(
                event: 'terminal.jail_escape_attempt',
                auditable: null,
                oldValues: null,
                newValues: [
                    'target' => $targetDir,
                    'resolved' => $resolvedPath,
                    'jail' => $jailPath,
                ],
                userId: $user->id
            );

            return [
                'command' => $command,
                'output' => "cd: permission denied: navigation outside account home directory is prohibited.\n",
                'exit_code' => 1,
                'cwd' => $cwd,
                'duration_ms' => $duration,
                'is_directory_change' => false,
            ];
        }

        return [
            'command' => $command,
            'output' => '',
            'exit_code' => 0,
            'cwd' => $resolvedPath,
            'duration_ms' => $duration,
            'is_directory_change' => true,
        ];
    }

    /**
     * Check if a path is strictly inside the customer's jail root.
     */
    protected function isWithinJail(string $path, string $jailRoot): bool
    {
        $normalizedPath = str_replace('\\', '/', realpath($path) ?: $path);
        $normalizedJail = str_replace('\\', '/', realpath($jailRoot) ?: $jailRoot);

        return str_starts_with($normalizedPath, $normalizedJail);
    }
}
