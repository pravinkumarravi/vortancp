<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Services;

use App\Modules\Agent\Services\AgentClientService;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\Ssl\Models\SslCertificate;
use Throwable;

class AutoSslFleetService
{
    public function __construct(
        protected SslCertificateService $certificateService,
        protected AgentClientService $agentClient,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Get aggregate fleet SSL health metrics across all hosted domains.
     *
     * @return array{total_domains: int, secured_domains: int, expiring_soon: int, unsecured_domains: int, coverage_pct: float}
     */
    public function getFleetOverview(): array
    {
        $totalDomains = Domain::where('status', Domain::STATUS_ACTIVE)->count();
        if ($totalDomains === 0) {
            return [
                'total_domains' => 0,
                'secured_domains' => 0,
                'expiring_soon' => 0,
                'unsecured_domains' => 0,
                'coverage_pct' => 100.0,
            ];
        }

        $now = now();
        $thirtyDays = $now->copy()->addDays(30);

        // Domains with currently valid active certificates
        $activeCerts = SslCertificate::where('status', SslCertificate::STATUS_ACTIVE)
            ->where('valid_until', '>', $now)
            ->get();

        $securedDomainIds = $activeCerts->pluck('domain_id')->unique();
        $securedCount = $securedDomainIds->count();

        // Expiring soon (within 30 days)
        $expiringCount = SslCertificate::where('status', SslCertificate::STATUS_ACTIVE)
            ->whereBetween('valid_until', [$now, $thirtyDays])
            ->pluck('domain_id')
            ->unique()
            ->count();

        $unsecuredCount = max(0, $totalDomains - $securedCount);
        $coveragePct = round(($securedCount / $totalDomains) * 100, 1);

        return [
            'total_domains' => $totalDomains,
            'secured_domains' => $securedCount,
            'expiring_soon' => $expiringCount,
            'unsecured_domains' => $unsecuredCount,
            'coverage_pct' => $coveragePct,
        ];
    }

    /**
     * Run AutoSSL scan and automated certificate issuance/renewal.
     *
     * @param int|null $domainId
     * @param int|null $accountId
     * @return array{total_scanned: int, issued: int, renewed: int, failed: int, results: array<int, array<string, mixed>>}
     */
    public function runAutoSsl(?int $domainId = null, ?int $accountId = null): array
    {
        $query = Domain::query()
            ->where('status', Domain::STATUS_ACTIVE)
            ->with(['hostingAccount', 'activeSslCertificate', 'virtualHost']);

        if ($domainId !== null) {
            $query->where('id', $domainId);
        }

        if ($accountId !== null) {
            $query->where('hosting_account_id', $accountId);
        }

        $domains = $query->get();
        $now = now();
        $thirtyDays = $now->copy()->addDays(30);

        $results = [];
        $issued = 0;
        $renewed = 0;
        $failed = 0;

        foreach ($domains as $domain) {
            $cert = $domain->activeSslCertificate;
            $needsIssuance = false;
            $isRenewal = false;

            if (!$cert) {
                $needsIssuance = true;
            } elseif ($cert->valid_until <= $thirtyDays || $cert->status !== SslCertificate::STATUS_ACTIVE) {
                $needsIssuance = true;
                $isRenewal = true;
            }

            if (!$needsIssuance) {
                $results[] = [
                    'domain' => $domain->domain,
                    'action' => 'skipped',
                    'message' => 'Certificate valid for more than 30 days.',
                    'expires_at' => $cert?->valid_until?->toDateString(),
                ];
                continue;
            }

            try {
                $newCert = $this->certificateService->issueLetsEncrypt($domain);

                // Reload Nginx via Agent
                try {
                    $this->agentClient->reloadService('nginx');
                } catch (Throwable) {
                    // Gracefully continue in local dev
                }

                if ($isRenewal) {
                    $renewed++;
                } else {
                    $issued++;
                }

                $results[] = [
                    'domain' => $domain->domain,
                    'action' => $isRenewal ? 'renewed' : 'issued',
                    'message' => "Let's Encrypt certificate successfully " . ($isRenewal ? 'renewed' : 'provisioned') . ".",
                    'expires_at' => $newCert->valid_until->toDateString(),
                ];
            } catch (Throwable $e) {
                $failed++;
                $results[] = [
                    'domain' => $domain->domain,
                    'action' => 'failed',
                    'message' => $e->getMessage(),
                    'expires_at' => null,
                ];
            }
        }

        $this->auditLogger->log(
            event: 'ssl.autossl_run_completed',
            newValues: [
                'scanned' => $domains->count(),
                'issued' => $issued,
                'renewed' => $renewed,
                'failed' => $failed,
            ]
        );

        return [
            'total_scanned' => $domains->count(),
            'issued' => $issued,
            'renewed' => $renewed,
            'failed' => $failed,
            'results' => $results,
        ];
    }
}
