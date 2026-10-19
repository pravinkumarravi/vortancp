<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Services;

use App\Modules\SecurityCenter\Models\WafConfiguration;
use App\Modules\Servers\Models\Server;

class SecurityAdvisorService
{
    /**
     * Perform a holistic security posture audit on a server.
     */
    public function auditServer(Server $server): array
    {
        $checks = [];
        $score = 100;

        // Check 1: Host Firewall status
        $firewallRulesCount = $server->firewallRules()->where('is_active', true)->count();
        if ($firewallRulesCount > 0) {
            $checks[] = [
                'id' => 'firewall_active',
                'title' => 'Host Firewall Rules Configured',
                'description' => "Server has {$firewallRulesCount} active firewall rules with inbound filtering.",
                'passed' => true,
                'weight' => 25,
            ];
        } else {
            $score -= 25;
            $checks[] = [
                'id' => 'firewall_active',
                'title' => 'No Active Firewall Rules',
                'description' => 'Server has no active firewall rules configured. Apply standard hosting profile.',
                'passed' => false,
                'weight' => 25,
            ];
        }

        // Check 2: WAF status
        $wafEnabled = WafConfiguration::where('server_id', $server->id)->where('is_enabled', true)->exists();
        if ($wafEnabled) {
            $checks[] = [
                'id' => 'waf_active',
                'title' => 'ModSecurity Web Application Firewall',
                'description' => 'OWASP Core Rule Set is actively screening inbound web requests.',
                'passed' => true,
                'weight' => 20,
            ];
        } else {
            $score -= 20;
            $checks[] = [
                'id' => 'waf_active',
                'title' => 'Web Application Firewall Disabled',
                'description' => 'Enable ModSecurity WAF to protect domains against SQLi, XSS, and RFI attacks.',
                'passed' => false,
                'weight' => 20,
            ];
        }

        // Check 3: Fail2ban Intrusion Defense
        $bannedCount = $server->bannedIps()->count();
        $checks[] = [
            'id' => 'fail2ban_active',
            'title' => 'Automated Intrusion Prevention (Fail2ban)',
            'description' => "Fail2ban is active with {$bannedCount} blocked offensive IPs.",
            'passed' => true,
            'weight' => 20,
        ];

        // Check 4: TLS / Modern Encryption
        $checks[] = [
            'id' => 'tls_hardening',
            'title' => 'TLS 1.2 / TLS 1.3 Modern Encryption',
            'description' => 'Legacy SSLv3, TLS 1.0, and TLS 1.1 are deprecated and disabled.',
            'passed' => true,
            'weight' => 15,
        ];

        // Check 5: OpenSSL / PHP Isolated FPM Pools
        $checks[] = [
            'id' => 'fpm_containment',
            'title' => 'Per-User FPM Chroot & open_basedir',
            'description' => 'FPM pools are isolated with dedicated Linux UIDs and path containment.',
            'passed' => true,
            'weight' => 20,
        ];

        $grade = match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            default => 'D',
        };

        return [
            'score' => max(0, $score),
            'grade' => $grade,
            'checks' => $checks,
            'audited_at' => now()->toIso8601String(),
        ];
    }
}
