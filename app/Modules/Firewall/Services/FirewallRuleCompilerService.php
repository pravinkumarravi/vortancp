<?php

declare(strict_types=1);

namespace App\Modules\Firewall\Services;

use App\Modules\Firewall\Models\FirewallRule;
use App\Modules\Servers\Models\Server;
use InvalidArgumentException;

class FirewallRuleCompilerService
{
    /**
     * Validate port number, port range, or 'any'.
     */
    public function validatePort(string $port): bool
    {
        $port = trim(strtolower($port));
        if ($port === 'any' || $port === '*' || $port === 'all') {
            return true;
        }

        // Port range: e.g. 20000:20050 or 20000-20050
        if (preg_match('/^(\d{1,5})[:\-](\d{1,5})$/', $port, $matches)) {
            $start = (int) $matches[1];
            $end = (int) $matches[2];
            if ($start >= 1 && $start <= 65535 && $end >= 1 && $end <= 65535 && $start <= $end) {
                return true;
            }
            throw new InvalidArgumentException("Invalid port range [{$port}]: bounds must be between 1 and 65535.");
        }

        // Single port: 1 - 65535
        if (ctype_digit($port)) {
            $num = (int) $port;
            if ($num >= 1 && $num <= 65535) {
                return true;
            }
        }

        throw new InvalidArgumentException("Invalid port specification [{$port}]. Provide a valid port (1-65535), range (e.g. 20000:20050), or 'any'.");
    }

    /**
     * Validate IP address, CIDR block, or 'any'.
     */
    public function validateSourceIp(string $source): bool
    {
        $source = trim(strtolower($source));
        if ($source === 'any' || $source === '0.0.0.0/0' || $source === '::/0') {
            return true;
        }

        // IPv4 single IP or CIDR
        if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(\/(\d{1,2}))?$/', $source, $m)) {
            $ip = $m[1];
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                throw new InvalidArgumentException("Invalid IPv4 address [{$ip}].");
            }
            if (isset($m[3])) {
                $mask = (int) $m[3];
                if ($mask < 0 || $mask > 32) {
                    throw new InvalidArgumentException("Invalid IPv4 subnet mask [/{$mask}].");
                }
            }
            return true;
        }

        // IPv6
        if (filter_var($source, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        throw new InvalidArgumentException("Invalid source IP or CIDR block [{$source}].");
    }

    /**
     * Compile standard Linux UFW command sequence.
     *
     * @return string[]
     */
    public function compileUfwCommands(Server $server): array
    {
        $commands = [];
        $commands[] = 'ufw --force reset';
        $commands[] = 'ufw default deny incoming';
        $commands[] = 'ufw default allow outgoing';

        $rules = $server->firewallRules()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            $action = match ($rule->action) {
                FirewallRule::ACTION_ALLOW => 'allow',
                FirewallRule::ACTION_DENY => 'deny',
                FirewallRule::ACTION_REJECT => 'reject',
                FirewallRule::ACTION_LIMIT => 'limit',
                default => 'allow',
            };

            $source = ($rule->source_ip === '0.0.0.0/0' || strtolower($rule->source_ip) === 'any')
                ? 'any'
                : $rule->source_ip;

            $proto = strtolower($rule->protocol);
            $port = strtolower($rule->port);

            $portClause = ($port === 'any' || $port === '*') ? '' : "port {$port}";
            $protoClause = ($proto === 'any') ? '' : "proto {$proto}";

            $cmd = "ufw {$action}";
            if ($protoClause !== '') {
                $cmd .= " {$protoClause}";
            }
            $cmd .= " from {$source} to any";
            if ($portClause !== '') {
                $cmd .= " {$portClause}";
            }

            if (! empty($rule->comment)) {
                $cleanComment = addcslashes($rule->comment, "'");
                $cmd .= " comment '{$cleanComment}'";
            }

            $commands[] = $cmd;
        }

        $commands[] = 'ufw --force enable';

        return $commands;
    }

    /**
     * Compile hardened IPTables shell script.
     */
    public function compileIptablesScript(Server $server): string
    {
        $lines = [];
        $lines[] = '#!/usr/bin/env bash';
        $lines[] = '# ==============================================================================';
        $lines[] = "# VortanCP Firewall Ruleset for Server: {$server->name} ({$server->hostname})";
        $lines[] = '# Generated at: ' . now()->toIso8601String();
        $lines[] = '# ==============================================================================';
        $lines[] = 'set -euo pipefail';
        $lines[] = '';
        $lines[] = '# Flush existing chains';
        $lines[] = 'iptables -F';
        $lines[] = 'iptables -X';
        $lines[] = 'iptables -t nat -F';
        $lines[] = 'iptables -t nat -X';
        $lines[] = '';
        $lines[] = '# Default policies';
        $lines[] = 'iptables -P INPUT DROP';
        $lines[] = 'iptables -P FORWARD DROP';
        $lines[] = 'iptables -P OUTPUT ACCEPT';
        $lines[] = '';
        $lines[] = '# Allow loopback';
        $lines[] = 'iptables -A INPUT -i lo -j ACCEPT';
        $lines[] = '';
        $lines[] = '# Allow established and related connections';
        $lines[] = 'iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT';
        $lines[] = '';

        $rules = $server->firewallRules()
            ->where('is_active', true)
            ->where('direction', FirewallRule::DIRECTION_INBOUND)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            $target = match ($rule->action) {
                FirewallRule::ACTION_ALLOW => 'ACCEPT',
                FirewallRule::ACTION_DENY => 'DROP',
                FirewallRule::ACTION_REJECT => 'REJECT',
                FirewallRule::ACTION_LIMIT => 'ACCEPT', // simulated
                default => 'ACCEPT',
            };

            $sourceClause = ($rule->source_ip === '0.0.0.0/0' || strtolower($rule->source_ip) === 'any')
                ? ''
                : "-s {$rule->source_ip}";

            $proto = strtolower($rule->protocol);
            $protoClause = ($proto === 'any') ? '' : "-p {$proto}";

            $port = strtolower($rule->port);
            $portClause = '';
            if ($port !== 'any' && $port !== '*' && $proto !== 'any') {
                $formattedPort = str_replace('-', ':', $port);
                $portClause = "--dport {$formattedPort}";
            }

            $commentClause = ! empty($rule->comment)
                ? '-m comment --comment "' . addcslashes($rule->comment, '"') . '"'
                : '';

            $tokens = array_filter(['iptables -A INPUT', $protoClause, $sourceClause, $portClause, $commentClause, "-j {$target}"]);
            $lines[] = implode(' ', $tokens);
        }

        $lines[] = '';
        return implode("\n", $lines) . "\n";
    }
}
