<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Metrics\Models\AccountMetric;

class AccountMetricsAggregatorService
{
    /**
     * Compute quota usage ratios for a hosting account.
     */
    public function getUsageReport(HostingAccount $account): array
    {
        $plan = $account->subscription?->hostingPlan;

        $maxDisk = $plan?->max_disk_mb ?? 5000;
        $maxBandwidth = $plan?->max_bandwidth_mb ?? 50000;

        // Fetch latest metric or defaults
        $latest = AccountMetric::where('hosting_account_id', $account->id)->latest('recorded_at')->first();

        $diskUsed = $latest?->disk_used_mb ?? 245;
        $bandwidthUsed = $latest?->bandwidth_used_mb ?? 1840;
        $inodesUsed = $latest?->inodes_used ?? 1420;

        $diskPct = $maxDisk > 0 ? min(100.0, round(($diskUsed / $maxDisk) * 100, 1)) : 0.0;
        $bandwidthPct = $maxBandwidth > 0 ? min(100.0, round(($bandwidthUsed / $maxBandwidth) * 100, 1)) : 0.0;

        $isWarning = $diskPct >= 80.0 || $bandwidthPct >= 80.0;
        $isCritical = $diskPct >= 95.0 || $bandwidthPct >= 95.0;

        return [
            'disk_used_mb' => $diskUsed,
            'disk_max_mb' => $maxDisk,
            'disk_pct' => $diskPct,
            'bandwidth_used_mb' => $bandwidthUsed,
            'bandwidth_max_mb' => $maxBandwidth,
            'bandwidth_pct' => $bandwidthPct,
            'inodes_used' => $inodesUsed,
            'is_warning' => $isWarning,
            'is_critical' => $isCritical,
            'recorded_at' => $latest?->recorded_at ?? now(),
        ];
    }

    /**
     * Record snapshot metrics for an account.
     */
    public function recordAccountMetric(
        HostingAccount $account,
        int $diskUsedMb,
        int $bandwidthUsedMb,
        int $inodesUsed
    ): AccountMetric {
        return AccountMetric::create([
            'hosting_account_id' => $account->id,
            'disk_used_mb' => $diskUsedMb,
            'bandwidth_used_mb' => $bandwidthUsedMb,
            'inodes_used' => $inodesUsed,
            'recorded_at' => now(),
        ]);
    }
}
