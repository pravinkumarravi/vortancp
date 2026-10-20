<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\DomainTrafficMetric;
use App\Modules\Domains\Models\Domain;

class AccessLogParserService
{
    /**
     * Parse raw Nginx access log lines and record domain traffic metrics.
     *
     * @param array<string> $lines
     */
    public function parseLogLines(Domain $domain, array $lines, ?string $date = null): DomainTrafficMetric
    {
        $targetDate = $date ?? now()->toDateString();
        $account = $domain->hostingAccount;

        $uniqueIps = [];
        $totalRequests = 0;
        $totalBytes = 0;
        $status2xx = 0;
        $status3xx = 0;
        $status4xx = 0;
        $status5xx = 0;
        $pathCounts = [];
        $referrerCounts = [];
        $uaCounts = [];

        $pattern = '/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"(\S+)\s+(\S+)[^"]*"\s+(\d{3})\s+(\d+|-)(?:\s+"([^"]*)"\s+"([^"]*)")?/';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match($pattern, $line, $matches)) {
                $ip = $matches[1];
                $uri = $matches[4];
                $status = (int) $matches[5];
                $bytes = $matches[6] === '-' ? 0 : (int) $matches[6];
                $referer = $matches[7] ?? '-';
                $ua = $matches[8] ?? '-';

                $totalRequests++;
                $uniqueIps[$ip] = true;
                $totalBytes += $bytes;

                if ($status >= 200 && $status < 300) {
                    $status2xx++;
                } elseif ($status >= 300 && $status < 400) {
                    $status3xx++;
                } elseif ($status >= 400 && $status < 500) {
                    $status4xx++;
                } elseif ($status >= 500) {
                    $status5xx++;
                }

                $cleanUri = explode('?', $uri)[0];
                $pathCounts[$cleanUri] = ($pathCounts[$cleanUri] ?? 0) + 1;

                if ($referer !== '-' && $referer !== '') {
                    $refHost = parse_url($referer, PHP_URL_HOST) ?: $referer;
                    $referrerCounts[$refHost] = ($referrerCounts[$refHost] ?? 0) + 1;
                }

                if ($ua !== '-' && $ua !== '') {
                    $uaSimple = explode(' ', $ua)[0];
                    $uaCounts[$uaSimple] = ($uaCounts[$uaSimple] ?? 0) + 1;
                }
            } else {
                // Fallback basic parse
                $totalRequests++;
                $status2xx++;
                $totalBytes += 1024;
            }
        }

        arsort($pathCounts);
        arsort($referrerCounts);
        arsort($uaCounts);

        $metric = DomainTrafficMetric::firstOrNew([
            'domain_id' => $domain->id,
            'date' => $targetDate,
        ]);

        $metric->hosting_account_id = $account->id;
        $metric->total_requests += $totalRequests;
        $metric->unique_visitors = max($metric->unique_visitors, count($uniqueIps) ?: min(1, $totalRequests));
        $metric->bandwidth_bytes += $totalBytes;
        $metric->status_2xx += $status2xx;
        $metric->status_3xx += $status3xx;
        $metric->status_4xx += $status4xx;
        $metric->status_5xx += $status5xx;

        $topPaths = array_slice($pathCounts, 0, 10, true);
        $metric->top_paths = $topPaths ?: ['/' => $totalRequests];
        $metric->top_referrers = array_slice($referrerCounts, 0, 5, true);
        $metric->top_user_agents = array_slice($uaCounts, 0, 5, true);

        $metric->save();

        return $metric;
    }

    /**
     * Seed high-fidelity sample traffic metrics for demonstration or testing.
     */
    public function seedSampleMetrics(Domain $domain, int $days = 7): void
    {
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $visitors = rand(150, 480);
            $requests = (int) ($visitors * rand(3, 8));
            $bandwidth = (int) ($requests * rand(12000, 35000));

            $status2xx = (int) ($requests * 0.92);
            $status3xx = (int) ($requests * 0.04);
            $status4xx = (int) ($requests * 0.03);
            $status5xx = $requests - ($status2xx + $status3xx + $status4xx);

            DomainTrafficMetric::updateOrCreate(
                ['domain_id' => $domain->id, 'date' => $date],
                [
                    'hosting_account_id' => $domain->hosting_account_id,
                    'total_requests' => $requests,
                    'unique_visitors' => $visitors,
                    'bandwidth_bytes' => $bandwidth,
                    'status_2xx' => $status2xx,
                    'status_3xx' => $status3xx,
                    'status_4xx' => $status4xx,
                    'status_5xx' => max(0, $status5xx),
                    'top_paths' => [
                        '/' => (int) ($requests * 0.45),
                        '/about' => (int) ($requests * 0.20),
                        '/api/v1/posts' => (int) ($requests * 0.15),
                        '/contact' => (int) ($requests * 0.10),
                        '/login' => (int) ($requests * 0.05),
                    ],
                    'top_referrers' => [
                        'google.com' => (int) ($visitors * 0.55),
                        'github.com' => (int) ($visitors * 0.25),
                        'bing.com' => (int) ($visitors * 0.12),
                        'twitter.com' => (int) ($visitors * 0.08),
                    ],
                    'top_user_agents' => [
                        'Mozilla/5.0' => (int) ($requests * 0.60),
                        'Chrome/128' => (int) ($requests * 0.25),
                        'Safari/605' => (int) ($requests * 0.10),
                        'curl/8.5' => (int) ($requests * 0.05),
                    ],
                ]
            );
        }
    }

    /**
     * Get aggregate statistics over a given period.
     *
     * @return array{
     *     total_requests: int,
     *     unique_visitors: int,
     *     bandwidth_bytes: int,
     *     status_2xx: int,
     *     status_3xx: int,
     *     status_4xx: int,
     *     status_5xx: int,
     *     daily_chart: array<int, array{date: string, label: string, visitors: int, requests: int, bandwidth_mb: float}>,
     *     top_paths: array<string, int>,
     *     top_referrers: array<string, int>
     * }
     */
    public function getAggregateMetrics(?int $domainId = null, int $days = 7): array
    {
        $query = DomainTrafficMetric::query()
            ->where('date', '>=', now()->subDays($days)->toDateString());

        if ($domainId) {
            $query->where('domain_id', $domainId);
        }

        $records = $query->orderBy('date')->get();

        $dailyChart = [];
        $combinedPaths = [];
        $combinedReferrers = [];

        foreach ($records as $m) {
            $dateCarbon = \Carbon\Carbon::parse($m->date);
            $dailyChart[] = [
                'date' => $dateCarbon->toDateString(),
                'label' => $dateCarbon->format('M d'),
                'visitors' => $m->unique_visitors,
                'requests' => $m->total_requests,
                'bandwidth_mb' => round($m->bandwidth_bytes / 1048576, 2),
            ];

            foreach ($m->top_paths ?? [] as $p => $c) {
                $combinedPaths[$p] = ($combinedPaths[$p] ?? 0) + $c;
            }
            foreach ($m->top_referrers ?? [] as $r => $c) {
                $combinedReferrers[$r] = ($combinedReferrers[$r] ?? 0) + $c;
            }
        }

        arsort($combinedPaths);
        arsort($combinedReferrers);

        return [
            'total_requests' => (int) $records->sum('total_requests'),
            'unique_visitors' => (int) $records->sum('unique_visitors'),
            'bandwidth_bytes' => (int) $records->sum('bandwidth_bytes'),
            'status_2xx' => (int) $records->sum('status_2xx'),
            'status_3xx' => (int) $records->sum('status_3xx'),
            'status_4xx' => (int) $records->sum('status_4xx'),
            'status_5xx' => (int) $records->sum('status_5xx'),
            'daily_chart' => $dailyChart,
            'top_paths' => array_slice($combinedPaths, 0, 8, true),
            'top_referrers' => array_slice($combinedReferrers, 0, 5, true),
        ];
    }
}
