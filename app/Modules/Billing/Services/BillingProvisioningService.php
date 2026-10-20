<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Domains\Models\Domain;
use App\Modules\Domains\Models\VirtualHost;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BillingProvisioningService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Provision a new hosting account from billing system.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createAccount(array $payload): array
    {
        $domainName = strtolower(trim((string) ($payload['domain'] ?? '')));
        if (empty($domainName)) {
            throw new InvalidArgumentException('Domain name is required.');
        }

        // Clean username or auto-generate from domain
        $username = strtolower(trim((string) ($payload['username'] ?? '')));
        if (empty($username)) {
            $cleaned = preg_replace('/[^a-z0-9]/', '', explode('.', $domainName)[0] ?? '');
            $username = substr($cleaned, 0, 8);
        }

        if (strlen($username) < 3 || strlen($username) > 16 || !ctype_alnum($username)) {
            throw new InvalidArgumentException('Username must be alphanumeric and between 3 and 16 characters.');
        }

        if (HostingAccount::where('username', $username)->exists()) {
            throw new InvalidArgumentException("Username [{$username}] is already taken.");
        }

        if (Domain::where('domain', $domainName)->exists()) {
            throw new InvalidArgumentException("Domain [{$domainName}] is already registered on this system.");
        }

        // Resolve Customer
        $customerEmail = trim((string) ($payload['customer_email'] ?? ''));
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid customer_email is required.');
        }

        $customerName = trim((string) ($payload['customer_name'] ?? '')) ?: explode('@', $customerEmail)[0];

        $customer = Customer::firstOrCreate(
            ['email' => $customerEmail],
            ['name' => $customerName, 'status' => Customer::STATUS_ACTIVE]
        );

        // Resolve Plan
        $plan = null;
        if (!empty($payload['plan_id'])) {
            $plan = HostingPlan::find($payload['plan_id']);
        } elseif (!empty($payload['plan_name'])) {
            $plan = HostingPlan::where('name', $payload['plan_name'])->first();
        }

        if (!$plan) {
            $plan = HostingPlan::where('is_active', true)->first();
            if (!$plan) {
                throw new InvalidArgumentException('No active hosting plan found.');
            }
        }

        // Resolve Server
        $server = null;
        if (!empty($payload['server_id'])) {
            $server = Server::find($payload['server_id']);
        }

        if (!$server) {
            $server = Server::where('status', 'active')->first();
            if (!$server) {
                throw new InvalidArgumentException('No active target server available.');
            }
        }

        return DB::transaction(function () use ($customer, $plan, $server, $username, $domainName) {
            // 1. Subscription
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'hosting_plan_id' => $plan->id,
                'server_id' => $server->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]);

            // 2. Hosting Account
            $homedir = "/var/vortan/users/{$username}";
            $account = HostingAccount::create([
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'server_id' => $server->id,
                'username' => $username,
                'primary_domain' => $domainName,
                'status' => HostingAccount::STATUS_ACTIVE,
                'uid' => 1000 + HostingAccount::count() + 1,
                'gid' => 1000 + HostingAccount::count() + 1,
                'homedir' => $homedir,
                'disk_used_mb' => 0,
                'bandwidth_used_mb' => 0,
            ]);

            $subscription->update(['hosting_account_id' => $account->id]);

            // 3. Primary Domain
            $domain = Domain::create([
                'hosting_account_id' => $account->id,
                'domain' => $domainName,
                'type' => Domain::TYPE_PRIMARY,
                'document_root' => "{$homedir}/public_html",
                'php_version' => '8.3',
                'force_https' => false,
                'status' => Domain::STATUS_ACTIVE,
            ]);

            // 4. Virtual Host
            VirtualHost::create([
                'domain_id' => $domain->id,
                'server_id' => $server->id,
                'webserver_type' => 'nginx',
                'config_path' => "/etc/nginx/sites-available/{$domainName}.conf",
                'is_ssl_enabled' => false,
                'last_compiled_at' => now(),
            ]);

            // 5. Default DNS Zone & Records
            $zone = DnsZone::create([
                'domain_id' => $domain->id,
                'hosting_account_id' => $account->id,
                'name' => $domainName,
                'status' => 'active',
                'serial' => (int) date('Ymd01'),
                'primary_ns' => 'ns1.vortancp.local',
                'admin_email' => 'hostmaster.' . $domainName,
            ]);

            DnsRecord::create([
                'dns_zone_id' => $zone->id,
                'name' => '@',
                'type' => DnsRecord::TYPE_A,
                'content' => $server->ip_address,
                'ttl' => 3600,
            ]);

            DnsRecord::create([
                'dns_zone_id' => $zone->id,
                'name' => 'www',
                'type' => DnsRecord::TYPE_CNAME,
                'content' => $domainName . '.',
                'ttl' => 3600,
            ]);

            DnsRecord::create([
                'dns_zone_id' => $zone->id,
                'name' => '@',
                'type' => DnsRecord::TYPE_MX,
                'content' => 'mail.' . $domainName . '.',
                'priority' => 10,
                'ttl' => 3600,
            ]);

            $this->auditLogger->log(
                event: 'billing.account_created',
                auditable: $account,
                newValues: [
                    'username' => $username,
                    'domain' => $domainName,
                    'plan' => $plan->name,
                    'server' => $server->hostname,
                ]
            );

            return [
                'status' => 'success',
                'message' => "Account [{$username}] successfully provisioned.",
                'data' => [
                    'account_id' => $account->id,
                    'username' => $account->username,
                    'domain' => $account->primary_domain,
                    'status' => $account->status,
                    'plan' => [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'disk_quota_mb' => $plan->disk_quota_mb,
                        'bandwidth_quota_mb' => $plan->bandwidth_quota_mb,
                    ],
                    'server' => [
                        'id' => $server->id,
                        'name' => $server->name,
                        'hostname' => $server->hostname,
                        'ip_address' => $server->ip_address,
                    ],
                    'homedir' => $account->homedir,
                    'created_at' => $account->created_at?->toISOString(),
                ],
            ];
        });
    }

    /**
     * Suspend an active hosting account.
     */
    public function suspendAccount(string $username, string $reason = 'Suspended by billing system'): array
    {
        $account = HostingAccount::where('username', strtolower(trim($username)))->first();
        if (!$account) {
            throw new NotFoundHttpException("Hosting account [{$username}] not found.");
        }

        $account->update([
            'status' => HostingAccount::STATUS_SUSPENDED,
            'suspended_reason' => $reason,
        ]);

        $this->auditLogger->log(
            event: 'billing.account_suspended',
            auditable: $account,
            newValues: ['status' => 'suspended', 'reason' => $reason]
        );

        return [
            'status' => 'success',
            'message' => "Account [{$username}] suspended successfully.",
            'data' => [
                'username' => $account->username,
                'status' => $account->status,
                'suspended_reason' => $account->suspended_reason,
            ],
        ];
    }

    /**
     * Unsuspend a previously suspended hosting account.
     */
    public function unsuspendAccount(string $username): array
    {
        $account = HostingAccount::where('username', strtolower(trim($username)))->first();
        if (!$account) {
            throw new NotFoundHttpException("Hosting account [{$username}] not found.");
        }

        $account->update([
            'status' => HostingAccount::STATUS_ACTIVE,
            'suspended_reason' => null,
        ]);

        $this->auditLogger->log(
            event: 'billing.account_unsuspended',
            auditable: $account,
            newValues: ['status' => 'active']
        );

        return [
            'status' => 'success',
            'message' => "Account [{$username}] unsuspended successfully.",
            'data' => [
                'username' => $account->username,
                'status' => $account->status,
            ],
        ];
    }

    /**
     * Terminate and permanently purge an account.
     */
    public function terminateAccount(string $username): array
    {
        $account = HostingAccount::where('username', strtolower(trim($username)))->first();
        if (!$account) {
            throw new NotFoundHttpException("Hosting account [{$username}] not found.");
        }

        return DB::transaction(function () use ($account, $username) {
            // Delete associated resources
            $account->domains()->delete();
            $account->subscription()->delete();
            $account->delete();

            $this->auditLogger->log(
                event: 'billing.account_terminated',
                auditable: $account,
                newValues: ['username' => $username, 'action' => 'terminated']
            );

            return [
                'status' => 'success',
                'message' => "Account [{$username}] terminated and purged permanently.",
            ];
        });
    }

    /**
     * Change / upgrade / downgrade an account's hosting plan.
     */
    public function changePackage(string $username, int|string $planIdentifier): array
    {
        $account = HostingAccount::where('username', strtolower(trim($username)))
            ->with('subscription')
            ->first();

        if (!$account) {
            throw new NotFoundHttpException("Hosting account [{$username}] not found.");
        }

        $plan = is_numeric($planIdentifier)
            ? HostingPlan::find((int) $planIdentifier)
            : HostingPlan::where('name', $planIdentifier)->first();

        if (!$plan) {
            throw new NotFoundHttpException("Hosting plan [{$planIdentifier}] not found.");
        }

        if ($account->subscription) {
            $account->subscription->update(['hosting_plan_id' => $plan->id]);
        }

        $this->auditLogger->log(
            event: 'billing.package_changed',
            auditable: $account,
            newValues: ['plan_id' => $plan->id, 'plan_name' => $plan->name]
        );

        return [
            'status' => 'success',
            'message' => "Account [{$username}] successfully upgraded/downgraded to plan [{$plan->name}].",
            'data' => [
                'username' => $account->username,
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'disk_quota_mb' => $plan->disk_quota_mb,
                    'bandwidth_quota_mb' => $plan->bandwidth_quota_mb,
                    'max_domains' => $plan->max_domains,
                    'max_databases' => $plan->max_databases,
                    'max_mailboxes' => $plan->max_mailboxes,
                ],
            ],
        ];
    }

    /**
     * Retrieve status and resource usage of an account.
     */
    public function getAccountStatus(string $username): array
    {
        $account = HostingAccount::where('username', strtolower(trim($username)))
            ->with(['subscription.hostingPlan', 'server', 'customer'])
            ->first();

        if (!$account) {
            throw new NotFoundHttpException("Hosting account [{$username}] not found.");
        }

        $plan = $account->subscription?->hostingPlan;

        return [
            'status' => 'success',
            'data' => [
                'id' => $account->id,
                'username' => $account->username,
                'primary_domain' => $account->primary_domain,
                'status' => $account->status,
                'suspended_reason' => $account->suspended_reason,
                'disk_used_mb' => $account->disk_used_mb,
                'disk_limit_mb' => $plan?->disk_quota_mb ?? 0,
                'bandwidth_used_mb' => $account->bandwidth_used_mb,
                'bandwidth_limit_mb' => $plan?->bandwidth_quota_mb ?? 0,
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                ] : null,
                'server' => $account->server ? [
                    'id' => $account->server->id,
                    'name' => $account->server->name,
                    'hostname' => $account->server->hostname,
                    'ip_address' => $account->server->ip_address,
                ] : null,
                'customer' => $account->customer ? [
                    'id' => $account->customer->id,
                    'name' => $account->customer->name,
                    'email' => $account->customer->email,
                ] : null,
                'created_at' => $account->created_at?->toISOString(),
            ],
        ];
    }

    /**
     * List hosting accounts with pagination.
     */
    public function listAccounts(int $page = 1, int $perPage = 50, ?string $status = null): array
    {
        $query = HostingAccount::query()
            ->with(['subscription.hostingPlan', 'server', 'customer']);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'status' => 'success',
            'data' => collect($paginator->items())->map(fn ($acc) => [
                'username' => $acc->username,
                'primary_domain' => $acc->primary_domain,
                'status' => $acc->status,
                'plan' => $acc->subscription?->hostingPlan?->name,
                'server' => $acc->server?->hostname,
                'disk_used_mb' => $acc->disk_used_mb,
                'bandwidth_used_mb' => $acc->bandwidth_used_mb,
                'created_at' => $acc->created_at?->toISOString(),
            ])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * List active hosting plans for billing synchronization.
     */
    public function listPlans(): array
    {
        $plans = HostingPlan::where('is_active', true)->get();

        return [
            'status' => 'success',
            'data' => $plans->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'disk_quota_mb' => $plan->disk_quota_mb,
                'bandwidth_quota_mb' => $plan->bandwidth_quota_mb,
                'max_domains' => $plan->max_domains,
                'max_subdomains' => $plan->max_subdomains,
                'max_databases' => $plan->max_databases,
                'max_mailboxes' => $plan->max_mailboxes,
                'max_ftp_accounts' => $plan->max_ftp_accounts,
                'has_ssl' => (bool) $plan->has_ssl,
                'has_dns' => (bool) $plan->has_dns_management,
            ])->all(),
        ];
    }
}
