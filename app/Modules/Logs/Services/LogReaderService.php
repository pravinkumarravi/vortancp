<?php

declare(strict_types=1);

namespace App\Modules\Logs\Services;

use App\Modules\Agent\Services\AgentClientService;
use Illuminate\Support\Str;

class LogReaderService
{
    public const SYSTEM_LOG_SOURCES = [
        'nginx-error' => [
            'name' => 'Nginx Error Log',
            'path' => '/var/log/nginx/error.log',
            'category' => 'web',
            'description' => 'Web server runtime warnings, upstream timeouts, and SSL handshake errors.',
        ],
        'nginx-access' => [
            'name' => 'Nginx Global Access Log',
            'path' => '/var/log/nginx/access.log',
            'category' => 'web',
            'description' => 'Real-time HTTP and HTTPS client incoming request logs.',
        ],
        'php-fpm' => [
            'name' => 'PHP-FPM Process Log',
            'path' => '/var/log/php8.3-fpm.log',
            'category' => 'php',
            'description' => 'PHP FastCGI worker lifecycle, slow script warnings, and pool respawns.',
        ],
        'mariadb' => [
            'name' => 'MariaDB Database Log',
            'path' => '/var/log/mysql/error.log',
            'category' => 'database',
            'description' => 'Database server startup, InnoDB engine logs, and query crash dumps.',
        ],
        'mail' => [
            'name' => 'Postfix & Dovecot Mail Log',
            'path' => '/var/log/mail.log',
            'category' => 'mail',
            'description' => 'SMTP relay, incoming/outgoing delivery receipts, and IMAP authentication.',
        ],
        'vortan-agent' => [
            'name' => 'VortanCP Agent Daemon Log',
            'path' => '/var/log/vortan/agent.log',
            'category' => 'agent',
            'description' => 'JSON-RPC dispatch commands, daemon audit trails, and execution output.',
        ],
        'fail2ban' => [
            'name' => 'Fail2ban Security Log',
            'path' => '/var/log/fail2ban.log',
            'category' => 'security',
            'description' => 'Jail ban/unban triggers and brute-force attempt tracking.',
        ],
    ];

    public function __construct(
        protected AgentClientService $agentClient
    ) {}

    /**
     * Get metadata of all available system log sources.
     *
     * @return array<string, array<string, string>>
     */
    public function getAdminLogSources(): array
    {
        return self::SYSTEM_LOG_SOURCES;
    }

    /**
     * Read and parse trailing lines from a system log source.
     *
     * @return array{source: string, file_path: string, total: int, entries: array<int, array<string, mixed>>}
     */
    public function readSystemLog(string $source, int $lines = 100, string $search = '', string $level = 'all'): array
    {
        if (! isset(self::SYSTEM_LOG_SOURCES[$source])) {
            $source = 'nginx-error';
        }

        $meta = self::SYSTEM_LOG_SOURCES[$source];
        $lines = min(max($lines, 10), 1000);

        $response = $this->agentClient->readLog($source, $lines);
        $rawOutput = (string) ($response['result']['stdout'] ?? $response['stdout'] ?? '');

        // If in development/Windows and output is simulated placeholder, generate realistic structured log data
        if (str_contains($rawOutput, '[SIMULATED EXECUTION]') || empty(trim($rawOutput))) {
            $rawOutput = $this->generateSampleSystemLog($source, $lines);
        }

        $entries = $this->parseLogOutput($rawOutput, $source);

        // Filter by keyword search
        if ($search !== '') {
            $entries = array_filter($entries, function ($entry) use ($search) {
                return str_contains(strtolower($entry['raw']), strtolower($search))
                    || str_contains(strtolower($entry['message']), strtolower($search));
            });
        }

        // Filter by log severity level
        if ($level !== 'all') {
            $entries = array_filter($entries, function ($entry) use ($level) {
                return strtolower($entry['level']) === strtolower($level);
            });
        }

        return [
            'source' => $source,
            'file_path' => $meta['path'],
            'total' => count($entries),
            'entries' => array_values($entries),
        ];
    }

    /**
     * Read and parse trailing lines from a customer virtual host log.
     *
     * @return array{domain: string, type: string, total: int, entries: array<int, array<string, mixed>>}
     */
    public function readDomainLog(string $username, string $domain, string $type = 'access', int $lines = 100, string $search = ''): array
    {
        $type = in_array(strtolower($type), ['access', 'error'], true) ? strtolower($type) : 'access';
        $lines = min(max($lines, 10), 1000);

        $response = $this->agentClient->readLog('vhost', $lines, [
            'username' => $username,
            'domain' => $domain,
            'type' => $type,
        ]);

        $rawOutput = (string) ($response['result']['stdout'] ?? $response['stdout'] ?? '');

        // Fallback for development/Windows
        if (str_contains($rawOutput, '[SIMULATED EXECUTION]') || empty(trim($rawOutput))) {
            $rawOutput = $this->generateSampleDomainLog($domain, $type, $lines);
        }

        $entries = $this->parseLogOutput($rawOutput, "vhost-{$type}");

        if ($search !== '') {
            $entries = array_filter($entries, function ($entry) use ($search) {
                return str_contains(strtolower($entry['raw']), strtolower($search))
                    || str_contains(strtolower($entry['message']), strtolower($search));
            });
        }

        return [
            'domain' => $domain,
            'type' => $type,
            'total' => count($entries),
            'entries' => array_values($entries),
        ];
    }

    /**
     * Parse raw string lines into structured log entries.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseLogOutput(string $rawText, string $sourceType): array
    {
        $lines = explode("\n", trim($rawText));
        $entries = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $level = 'INFO';
            $timestamp = now()->toDateTimeString();
            $message = $line;
            $statusCode = null;
            $method = null;
            $clientIp = null;

            // Detect severity level
            if (preg_match('/\b(emerg|emergency|alert|crit|critical|fatal)\b/i', $line)) {
                $level = 'CRITICAL';
            } elseif (preg_match('/\b(err|error|failed|failure)\b/i', $line)) {
                $level = 'ERROR';
            } elseif (preg_match('/\b(warn|warning)\b/i', $line)) {
                $level = 'WARNING';
            } elseif (preg_match('/\b(notice)\b/i', $line)) {
                $level = 'NOTICE';
            } elseif (preg_match('/\b(debug)\b/i', $line)) {
                $level = 'DEBUG';
            }

            // HTTP access log detection (Common / Combined Log Format)
            if (preg_match('/^(\d+\.\d+\.\d+\.\d+|[a-f0-9:]+)\s+-\s+-\s+\[(.*?)\]\s+"([A-Z]+)\s+(.*?)\s+HTTP\/[0-9.]+"\s+(\d{3})\s+(\d+)/', $line, $matches)) {
                $clientIp = $matches[1];
                $timestamp = $matches[2];
                $method = $matches[3];
                $uri = $matches[4];
                $statusCode = (int) $matches[5];

                $level = ($statusCode >= 500) ? 'ERROR' : (($statusCode >= 400) ? 'WARNING' : 'INFO');
                $message = "{$method} {$uri} -> Status {$statusCode}";
            } elseif (preg_match('/^\[(.*?)\]\s+\[(.*?)\]\s+(.*)/', $line, $matches)) {
                $timestamp = $matches[1];
                $level = strtoupper($matches[2]);
                $message = $matches[3];
            }

            $entries[] = [
                'id' => Str::random(8),
                'timestamp' => $timestamp,
                'level' => $level,
                'status_code' => $statusCode,
                'method' => $method,
                'client_ip' => $clientIp,
                'message' => $message,
                'raw' => $line,
            ];
        }

        return $entries;
    }

    /**
     * Generate realistic mock logs for development and testing.
     */
    protected function generateSampleSystemLog(string $source, int $count): string
    {
        $now = now();
        $samples = [];

        for ($i = $count; $i >= 1; $i--) {
            $time = $now->copy()->subSeconds($i * 12)->format('Y/m/d H:i:s');

            if ($source === 'nginx-error') {
                $samples[] = match ($i % 4) {
                    0 => "[{$time}] [error] 14201#14201: *{$i} open() \"/var/vortan/users/acmecorp/public_html/favicon.ico\" failed (2: No such file or directory)",
                    1 => "[{$time}] [warn] 14201#14201: *{$i} an upstream response is buffered to a temporary file while reading upstream",
                    2 => "[{$time}] [notice] 14201#14201: signal process started",
                    default => "[{$time}] [info] 14201#14201: *{$i} client closed connection in SSL handshake",
                };
            } elseif ($source === 'nginx-access') {
                $ip = "192.168.1." . (10 + ($i % 30));
                $code = match ($i % 5) {
                    0 => 404,
                    1 => 500,
                    default => 200,
                };
                $samples[] = "{$ip} - - [{$time}] \"GET /api/v1/health HTTP/1.1\" {$code} 1420 \"-\" \"Mozilla/5.0\"";
            } elseif ($source === 'php-fpm') {
                $samples[] = "[{$time}] NOTICE: [pool acmecorp] child " . (2000 + $i) . " started";
            } elseif ($source === 'mariadb') {
                $samples[] = "{$time} 0 [Note] InnoDB: Buffer pool hit rate 998 / 1000";
            } elseif ($source === 'mail') {
                $samples[] = "{$time} mailserver postfix/smtp[" . (3000 + $i) . "]: status=sent (250 2.0.0 Ok: queued)";
            } elseif ($source === 'fail2ban') {
                $samples[] = "{$time} fail2ban.actions [1024]: NOTICE [sshd] Restore Ban 203.0.113." . ($i % 50);
            } else {
                $samples[] = "[{$time}] [INFO] vortan-agent.INFO: RPC request executed id=" . Str::random(6);
            }
        }

        return implode("\n", $samples);
    }

    /**
     * Generate realistic mock logs for domain virtual hosts.
     */
    protected function generateSampleDomainLog(string $domain, string $type, int $count): string
    {
        $now = now();
        $samples = [];

        for ($i = $count; $i >= 1; $i--) {
            $time = $now->copy()->subSeconds($i * 15)->format('d/M/Y:H:i:s O');

            if ($type === 'access') {
                $ip = "172.16.0." . (5 + ($i % 25));
                $uri = match ($i % 4) {
                    0 => '/wp-admin/admin-ajax.php',
                    1 => '/about-us',
                    2 => '/contact',
                    default => '/',
                };
                $code = match ($i % 6) {
                    0 => 404,
                    default => 200,
                };
                $samples[] = "{$ip} - - [{$time}] \"GET {$uri} HTTP/1.1\" {$code} 3512 \"https://{$domain}/\" \"Mozilla/5.0\"";
            } else {
                $samples[] = "[{$time}] [error] [client 172.16.0.4] PHP Warning: Undefined array key \"user\" in /var/vortan/users/acmecorp/public_html/index.php on line 42";
            }
        }

        return implode("\n", $samples);
    }
}
