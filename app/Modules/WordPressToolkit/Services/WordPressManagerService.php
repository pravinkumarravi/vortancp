<?php

declare(strict_types=1);

namespace App\Modules\WordPressToolkit\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Databases\Services\DatabaseManagerService;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Services\EntitlementService;
use App\Modules\WordPressToolkit\Models\WordPressInstance;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WordPressManagerService
{
    public function __construct(
        protected WordPressConfigCompilerService $compiler,
        protected DatabaseManagerService $databaseManager,
        protected EntitlementService $entitlementService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Provision and install a new WordPress instance.
     */
    public function install(
        Domain $domain,
        string $siteTitle,
        string $adminEmail,
        string $subPath = '',
        bool $autoUpdateCore = true,
        string $wpVersion = '6.7.1'
    ): WordPressInstance {
        $account = $domain->hostingAccount;

        if (! $this->entitlementService->hasFeature($account, 'wordpress')) {
            throw new QuotaExceededException("WordPress Toolkit is disabled under account hosting plan.");
        }

        $cleanSubPath = trim(str_replace('\\', '/', $subPath), '/');
        if (str_contains($cleanSubPath, '..') || str_contains($cleanSubPath, "\0")) {
            throw new InvalidArgumentException("Path traversal detected in WordPress install path.");
        }

        $docRoot = rtrim($domain->document_root, '/');
        $installDir = $cleanSubPath === '' ? $docRoot : "{$docRoot}/{$cleanSubPath}";

        if (! File::isDirectory($installDir)) {
            File::makeDirectory($installDir, 0755, true);
        }

        // 1. Provision isolated database & user
        $dbSuffix = 'wp' . Str::lower(Str::random(3));
        $userSuffix = 'u' . Str::lower(Str::random(3));
        $dbPassword = Str::random(16) . 'A1!';

        $database = $this->databaseManager->createDatabase($account, $dbSuffix);
        $dbUser = $this->databaseManager->createDatabaseUser($account, $userSuffix, $dbPassword);
        $this->databaseManager->assignUserToDatabase($database, $dbUser, ['ALL']);

        // 2. Generate wp-config.php
        $tablePrefix = 'wp_' . Str::lower(Str::random(4)) . '_';
        $wpConfigContent = $this->compiler->compileWpConfig(
            dbName: $database->name,
            dbUser: $dbUser->username,
            dbPassword: $dbPassword,
            tablePrefix: $tablePrefix,
            disallowFileEdit: true,
            debug: false,
            autoUpdateCore: $autoUpdateCore
        );

        File::put("{$installDir}/wp-config.php", $wpConfigContent);

        // 3. Create WordPress core file placeholders
        if (! File::exists("{$installDir}/index.php")) {
            File::put("{$installDir}/index.php", "<?php\n/** WordPress Index */\ndefine('WP_USE_THEMES', true);\nrequire __DIR__ . '/wp-blog-header.php';\n");
        }
        if (! File::exists("{$installDir}/wp-settings.php")) {
            File::put("{$installDir}/wp-settings.php", "<?php\n/** WordPress Settings Core */\n");
        }

        $siteUrl = 'http://' . $domain->domain . ($cleanSubPath !== '' ? "/{$cleanSubPath}" : '');

        $instance = WordPressInstance::create([
            'hosting_account_id' => $account->id,
            'domain_id' => $domain->id,
            'site_title' => $siteTitle,
            'site_url' => $siteUrl,
            'path' => $installDir,
            'db_name' => $database->name,
            'db_user' => $dbUser->username,
            'wp_version' => $wpVersion,
            'admin_email' => $adminEmail,
            'status' => WordPressInstance::STATUS_ACTIVE,
            'auto_update_core' => $autoUpdateCore,
            'auto_update_plugins' => false,
            'xmlrpc_blocked' => true,
            'file_editing_disabled' => true,
            'table_prefix' => $tablePrefix,
            'last_scanned_at' => now(),
        ]);

        $this->auditLogger->log(
            event: 'wordpress.instance_installed',
            auditable: $instance,
            oldValues: null,
            newValues: [
                'site_title' => $siteTitle,
                'site_url' => $siteUrl,
                'db_name' => $database->name,
            ]
        );

        return $instance;
    }

    /**
     * Toggle Maintenance mode on/off.
     */
    public function toggleMaintenance(WordPressInstance $instance): void
    {
        $maintFile = rtrim($instance->path, '/') . '/.maintenance';

        if ($instance->status === WordPressInstance::STATUS_MAINTENANCE) {
            if (File::exists($maintFile)) {
                File::delete($maintFile);
            }
            $instance->update(['status' => WordPressInstance::STATUS_ACTIVE]);
        } else {
            File::put($maintFile, $this->compiler->compileMaintenanceFile());
            $instance->update(['status' => WordPressInstance::STATUS_MAINTENANCE]);
        }

        $this->auditLogger->log(
            event: 'wordpress.maintenance_toggled',
            auditable: $instance,
            oldValues: null,
            newValues: ['status' => $instance->status]
        );
    }

    /**
     * Toggle security hardening feature (e.g. XML-RPC blocking or file editing).
     */
    public function toggleHardening(WordPressInstance $instance, string $feature): void
    {
        if ($feature === 'file_editing') {
            $newVal = ! $instance->file_editing_disabled;
            $instance->update(['file_editing_disabled' => $newVal]);
        } elseif ($feature === 'xmlrpc') {
            $newVal = ! $instance->xmlrpc_blocked;
            $instance->update(['xmlrpc_blocked' => $newVal]);
        }

        $this->auditLogger->log(
            event: 'wordpress.hardening_updated',
            auditable: $instance,
            oldValues: null,
            newValues: [$feature => $newVal]
        );
    }

    /**
     * Generate single sign-on redirect URL to WP-Admin.
     */
    public function generateSsoUrl(WordPressInstance $instance): string
    {
        $token = Str::random(32);
        return rtrim($instance->site_url, '/') . "/wp-login.php?vortan_sso={$token}";
    }

    /**
     * Delete WordPress instance.
     */
    public function deleteInstance(WordPressInstance $instance): void
    {
        $this->auditLogger->log(
            event: 'wordpress.instance_deleted',
            auditable: $instance,
            oldValues: ['site_title' => $instance->site_title, 'path' => $instance->path],
            newValues: null
        );

        $instance->delete();
    }
}
