<?php

declare(strict_types=1);

namespace App\Modules\HostingPlans\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Models\HostingPlan;

class EntitlementService
{
    /**
     * Resolve the active hosting plan governing an account.
     */
    public function getPlan(HostingAccount $account): ?HostingPlan
    {
        return $account->subscription?->hostingPlan;
    }

    /**
     * Assert whether an account can provision an additional resource.
     *
     * @throws QuotaExceededException
     */
    public function assertCan(HostingAccount $account, string $resourceType, int $currentCount = 0): void
    {
        // Suspended accounts are barred from creating any new resources
        if (!$account->isActive()) {
            throw new QuotaExceededException("Account [{$account->username}] is suspended ({$account->suspended_reason}). Creation of {$resourceType} is prohibited.");
        }

        $plan = $this->getPlan($account);

        if (!$plan) {
            return; // If no plan assigned, assume unconstrained by default
        }

        $maxAllowed = match ($resourceType) {
            'domain' => $plan->max_domains,
            'subdomain' => $plan->max_subdomains,
            'database' => $plan->max_databases,
            'mailbox' => $plan->max_mailboxes,
            'ftp' => $plan->max_ftp_accounts,
            'cron' => $plan->max_cron_jobs,
            default => 999999,
        };

        if ($currentCount >= $maxAllowed) {
            throw new QuotaExceededException(
                "Resource quota exceeded for {$resourceType}: Account has {$currentCount} of maximum {$maxAllowed} allowed under plan [{$plan->name}]."
            );
        }
    }

    /**
     * Check feature flag availability.
     */
    public function hasFeature(HostingAccount $account, string $feature): bool
    {
        if (!$account->isActive()) {
            return false;
        }

        $plan = $this->getPlan($account);

        if (!$plan) {
            return true;
        }

        return match ($feature) {
            'ssh' => $plan->has_ssh_access,
            'ssl' => $plan->has_ssl,
            'git' => $plan->has_git,
            'dns' => $plan->has_dns_management,
            'wordpress' => $plan->has_wordpress_toolkit,
            default => false,
        };
    }
}
