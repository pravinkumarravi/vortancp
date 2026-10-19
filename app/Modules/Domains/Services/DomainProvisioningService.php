<?php

declare(strict_types=1);

namespace App\Modules\Domains\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\Domains\Models\VirtualHost;
use App\Modules\HostingPlans\Services\EntitlementService;
use InvalidArgumentException;

class DomainProvisioningService
{
    public function __construct(
        protected EntitlementService $entitlements,
        protected VirtualHostCompilerService $compiler,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Provision a new domain (primary, addon, subdomain, alias).
     */
    public function provision(
        HostingAccount $account,
        string $domainName,
        string $type = Domain::TYPE_ADDON,
        ?string $phpVersion = '8.4',
        ?string $customDocRoot = null,
        ?Domain $parentDomain = null,
    ): Domain {
        $cleanDomain = strtolower(trim($domainName));

        // 1. Validate domain syntax
        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/', $cleanDomain)) {
            throw new InvalidArgumentException("Invalid domain name format [{$cleanDomain}].");
        }

        // 2. Validate uniqueness
        if (Domain::where('domain', $cleanDomain)->exists()) {
            throw new InvalidArgumentException("The domain [{$cleanDomain}] is already hosted on this system.");
        }

        // 3. Quota check via EntitlementService
        $quotaKey = $type === Domain::TYPE_SUBDOMAIN ? 'subdomain' : 'domain';
        $currentCount = $account->domains()->where('type', $type)->count();
        $this->entitlements->assertCan($account, $quotaKey, $currentCount);

        // 4. Resolve Document Root
        $docRoot = match ($type) {
            Domain::TYPE_PRIMARY => $customDocRoot ?: "{$account->homedir}/public_html",
            Domain::TYPE_SUBDOMAIN => $customDocRoot ?: "{$account->homedir}/subdomains/{$cleanDomain}",
            Domain::TYPE_ALIAS => $parentDomain ? $parentDomain->document_root : "{$account->homedir}/public_html",
            default => $customDocRoot ?: "{$account->homedir}/{$cleanDomain}",
        };

        // 5. Create Domain Record
        $domain = Domain::create([
            'hosting_account_id' => $account->id,
            'domain' => $cleanDomain,
            'type' => $type,
            'parent_domain_id' => $parentDomain?->id,
            'document_root' => $docRoot,
            'php_version' => $phpVersion ?: '8.4',
            'status' => Domain::STATUS_ACTIVE,
            'force_https' => true,
            'www_redirect' => 'none',
        ]);

        // 6. Generate and register Virtual Host config
        $configPath = "/etc/nginx/sites-available/{$cleanDomain}.conf";
        VirtualHost::create([
            'domain_id' => $domain->id,
            'server_id' => $account->server_id,
            'webserver_type' => 'nginx',
            'config_path' => $configPath,
            'is_ssl_enabled' => false,
            'last_compiled_at' => now(),
        ]);

        // 7. Audit Log
        $this->auditLogger->log(
            event: 'domain.created',
            auditable: $domain,
            newValues: [
                'domain' => $cleanDomain,
                'type' => $type,
                'account' => $account->username,
                'docroot' => $docRoot,
                'php_version' => $phpVersion,
            ]
        );

        return $domain;
    }

    /**
     * Delete a domain and cleanup virtual host.
     */
    public function delete(Domain $domain): void
    {
        if ($domain->isPrimary()) {
            throw new InvalidArgumentException("Cannot delete primary domain [{$domain->domain}] directly. Delete the hosting account instead.");
        }

        $domainName = $domain->domain;
        $account = $domain->hostingAccount;

        $this->auditLogger->log(
            event: 'domain.deleted',
            auditable: $domain,
            oldValues: [
                'domain' => $domainName,
                'type' => $domain->type,
                'account' => $account->username,
            ]
        );

        $domain->delete();
    }
}
