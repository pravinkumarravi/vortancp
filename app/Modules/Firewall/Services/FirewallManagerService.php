<?php

declare(strict_types=1);

namespace App\Modules\Firewall\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Firewall\Models\BannedIp;
use App\Modules\Firewall\Models\FirewallRule;
use App\Modules\Servers\Models\Server;
use InvalidArgumentException;

class FirewallManagerService
{
    public function __construct(
        protected FirewallRuleCompilerService $compiler,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Add a firewall rule for a target server.
     */
    public function createRule(
        Server $server,
        string $port,
        string $protocol = FirewallRule::PROTO_TCP,
        string $action = FirewallRule::ACTION_ALLOW,
        string $sourceIp = '0.0.0.0/0',
        string $direction = FirewallRule::DIRECTION_INBOUND,
        ?string $comment = null,
        int $priority = 100
    ): FirewallRule {
        $this->compiler->validatePort($port);
        $this->compiler->validateSourceIp($sourceIp);

        $rule = FirewallRule::create([
            'server_id' => $server->id,
            'direction' => $direction,
            'action' => $action,
            'protocol' => strtolower($protocol),
            'port' => $port,
            'source_ip' => $sourceIp,
            'comment' => $comment,
            'priority' => $priority,
            'is_active' => true,
        ]);

        $this->auditLogger->log(
            event: 'firewall.rule_created',
            auditable: $rule,
            oldValues: null,
            newValues: [
                'server' => $server->name,
                'port' => $port,
                'protocol' => $protocol,
                'action' => $action,
                'source' => $sourceIp,
            ]
        );

        return $rule;
    }

    /**
     * Toggle active state of a firewall rule.
     */
    public function toggleRule(FirewallRule $rule): void
    {
        $old = $rule->is_active;
        $rule->update([
            'is_active' => ! $rule->is_active,
        ]);

        $this->auditLogger->log(
            event: 'firewall.rule_toggled',
            auditable: $rule,
            oldValues: ['is_active' => $old],
            newValues: ['is_active' => $rule->is_active]
        );
    }

    /**
     * Delete a firewall rule.
     */
    public function deleteRule(FirewallRule $rule): void
    {
        $this->auditLogger->log(
            event: 'firewall.rule_deleted',
            auditable: $rule,
            oldValues: [
                'port' => $rule->port,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
            ],
            newValues: null
        );

        $rule->delete();
    }

    /**
     * Seed or apply standard web hosting firewall profile.
     */
    public function applyStandardHostingProfile(Server $server): int
    {
        $profiles = [
            ['port' => '22', 'proto' => 'tcp', 'action' => 'limit', 'comment' => 'SSH Remote Administration'],
            ['port' => '80', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'HTTP Web Traffic'],
            ['port' => '443', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'HTTPS Encrypted Web Traffic'],
            ['port' => '21', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'FTP Control Port'],
            ['port' => '20000:20050', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'FTP Passive Data Ports'],
            ['port' => '25', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'SMTP Mail Relay'],
            ['port' => '587', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'SMTP Submission'],
            ['port' => '465', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'SMTPS Mail Submission'],
            ['port' => '993', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'IMAPS Encrypted Mail'],
            ['port' => '995', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'POP3S Encrypted Mail'],
            ['port' => '53', 'proto' => 'udp', 'action' => 'allow', 'comment' => 'DNS Nameserver Queries (UDP)'],
            ['port' => '53', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'DNS Zone Transfers (TCP)'],
            ['port' => '8443', 'proto' => 'tcp', 'action' => 'allow', 'comment' => 'VortanCP Control Panel'],
        ];

        $added = 0;
        foreach ($profiles as $idx => $p) {
            $exists = $server->firewallRules()
                ->where('port', $p['port'])
                ->where('protocol', $p['proto'])
                ->where('action', $p['action'])
                ->exists();

            if (! $exists) {
                $this->createRule(
                    server: $server,
                    port: $p['port'],
                    protocol: $p['proto'],
                    action: $p['action'],
                    sourceIp: '0.0.0.0/0',
                    direction: FirewallRule::DIRECTION_INBOUND,
                    comment: $p['comment'],
                    priority: 10 + ($idx * 5)
                );
                $added++;
            }
        }

        return $added;
    }

    /**
     * Manually or automatically ban an abusive IP address.
     */
    public function banIp(
        Server $server,
        string $ip,
        string $jail = 'manual',
        ?string $reason = null,
        ?int $durationHours = 24
    ): BannedIp {
        $cleanIp = trim($ip);
        if (! filter_var($cleanIp, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("Invalid IP address [{$cleanIp}] for ban list.");
        }

        $expiresAt = $durationHours ? now()->addHours($durationHours) : null;

        $banned = BannedIp::updateOrCreate(
            ['server_id' => $server->id, 'ip_address' => $cleanIp],
            [
                'jail' => $jail,
                'failures_count' => 5,
                'reason' => $reason ?? 'Administrative manual block',
                'banned_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );

        $this->auditLogger->log(
            event: 'firewall.ip_banned',
            auditable: $banned,
            oldValues: null,
            newValues: [
                'server' => $server->name,
                'ip' => $cleanIp,
                'jail' => $jail,
                'reason' => $reason,
            ]
        );

        return $banned;
    }

    /**
     * Unban an IP address.
     */
    public function unbanIp(BannedIp $bannedIp): void
    {
        $this->auditLogger->log(
            event: 'firewall.ip_unbanned',
            auditable: $bannedIp,
            oldValues: [
                'server_id' => $bannedIp->server_id,
                'ip' => $bannedIp->ip_address,
                'jail' => $bannedIp->jail,
            ],
            newValues: null
        );

        $bannedIp->delete();
    }
}
