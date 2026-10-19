<?php

declare(strict_types=1);

namespace App\Modules\PhpManager\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\PhpManager\Models\PhpPool;

class PhpManagerService
{
    public function __construct(
        protected PhpFpmPoolCompilerService $compiler,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Ensure a dedicated per-user PHP-FPM process pool exists.
     */
    public function ensurePool(HostingAccount $account, string $version = '8.4'): PhpPool
    {
        $existing = PhpPool::where('hosting_account_id', $account->id)
            ->where('version', $version)
            ->first();

        if ($existing) {
            return $existing;
        }

        $socketPath = "/run/php/php{$version}-fpm-{$account->username}.sock";

        $pool = PhpPool::create([
            'hosting_account_id' => $account->id,
            'version' => $version,
            'socket_path' => $socketPath,
            'pm_type' => 'ondemand',
            'pm_max_children' => 10,
            'memory_limit' => '256M',
            'max_execution_time' => 60,
            'upload_max_filesize' => '64M',
            'post_max_size' => '64M',
            'display_errors' => false,
        ]);

        $this->auditLogger->log(
            event: 'php_pool.created',
            auditable: $pool,
            newValues: ['username' => $account->username, 'version' => $version]
        );

        return $pool;
    }

    /**
     * Update PHP INI directives for a pool.
     */
    public function updatePoolDirectives(PhpPool $pool, array $data): PhpPool
    {
        $old = $pool->toArray();

        $pool->update([
            'memory_limit' => $data['memory_limit'] ?? $pool->memory_limit,
            'max_execution_time' => (int) ($data['max_execution_time'] ?? $pool->max_execution_time),
            'upload_max_filesize' => $data['upload_max_filesize'] ?? $pool->upload_max_filesize,
            'post_max_size' => $data['post_max_size'] ?? $pool->post_max_size,
            'display_errors' => (bool) ($data['display_errors'] ?? $pool->display_errors),
        ]);

        $this->auditLogger->log(
            event: 'php_pool.directives_updated',
            auditable: $pool,
            oldValues: $old,
            newValues: $pool->toArray()
        );

        return $pool;
    }

    /**
     * Change a domain's PHP engine version and trigger vhost recompilation.
     */
    public function changeDomainPhpVersion(Domain $domain, string $newVersion): void
    {
        $oldVersion = $domain->php_version;
        $domain->update(['php_version' => $newVersion]);

        $this->auditLogger->log(
            event: 'domain.php_version_changed',
            auditable: $domain,
            oldValues: ['php_version' => $oldVersion],
            newValues: ['php_version' => $newVersion]
        );
    }

    /**
     * Compile pool configuration file.
     */
    public function compilePool(PhpPool $pool): string
    {
        return $this->compiler->compile($pool);
    }
}
