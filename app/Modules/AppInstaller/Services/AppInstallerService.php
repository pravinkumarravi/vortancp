<?php

declare(strict_types=1);

namespace App\Modules\AppInstaller\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Databases\Services\DatabaseManagerService;
use App\Modules\Domains\Models\Domain;
use App\Modules\AppInstaller\Models\InstalledApp;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AppInstallerService
{
    public function __construct(
        protected AppCatalogService $catalogService,
        protected DatabaseManagerService $databaseManager,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Provision and install an application package.
     */
    public function install(
        Domain $domain,
        string $appSlug,
        string $adminEmail,
        string $subPath = ''
    ): InstalledApp {
        $package = $this->catalogService->getPackage($appSlug);
        if (! $package) {
            throw new InvalidArgumentException("Unknown application package '{$appSlug}'.");
        }

        $account = $domain->hostingAccount;

        // Check PHP compatibility
        $currentPhp = $domain->php_version ?? '8.3';
        if (version_compare($currentPhp, $package['min_php'], '<')) {
            throw new InvalidArgumentException(
                "Domain PHP version ({$currentPhp}) is lower than required minimum ({$package['min_php']}) for {$package['name']}."
            );
        }

        $cleanSubPath = trim(str_replace('\\', '/', $subPath), '/');
        if (str_contains($cleanSubPath, '..') || str_contains($cleanSubPath, "\0")) {
            throw new InvalidArgumentException("Path traversal detected in install path.");
        }

        $docRoot = rtrim(str_replace('\\', '/', $domain->document_root), '/');
        $installDir = $cleanSubPath === '' ? $docRoot : "{$docRoot}/{$cleanSubPath}";

        if (! File::isDirectory($installDir)) {
            File::makeDirectory($installDir, 0755, true);
        }

        $dbName = null;
        $dbUsername = null;

        // Provision database if application requires it
        if ($package['requires_db']) {
            $dbSuffix = Str::lower(substr($package['slug'], 0, 3)) . Str::lower(Str::random(3));
            $userSuffix = 'u' . Str::lower(Str::random(3));
            $dbPassword = Str::random(16) . 'A1!';

            $database = $this->databaseManager->createDatabase($account, $dbSuffix);
            $dbUser = $this->databaseManager->createDatabaseUser($account, $userSuffix, $dbPassword);
            $this->databaseManager->assignUserToDatabase($database, $dbUser, ['ALL']);

            $dbName = $database->name;
            $dbUsername = $dbUser->username;

            // Generate application configuration file
            $this->generateAppConfigFile($package['slug'], $installDir, $dbName, $dbUsername, $dbPassword, $adminEmail);
        } else {
            // Static / standalone application entry
            if (! File::exists("{$installDir}/index.php")) {
                File::put(
                    "{$installDir}/index.php",
                    "<?php\n/** {$package['name']} Installed via VortanCP */\necho '<h1>Welcome to {$package['name']}</h1>';\n"
                );
            }
        }

        $siteUrl = 'http://' . $domain->domain . ($cleanSubPath !== '' ? "/{$cleanSubPath}" : '');

        $app = InstalledApp::create([
            'hosting_account_id' => $account->id,
            'domain_id' => $domain->id,
            'app_slug' => $package['slug'],
            'app_name' => $package['name'],
            'version' => $package['version'],
            'install_path' => $installDir,
            'site_url' => $siteUrl,
            'db_name' => $dbName,
            'db_user' => $dbUsername,
            'admin_email' => $adminEmail,
            'status' => InstalledApp::STATUS_INSTALLED,
            'installed_at' => now(),
        ]);

        $this->auditLogger->log(
            event: 'app_installer.installed',
            auditable: $app,
            oldValues: null,
            newValues: [
                'app' => $package['name'],
                'url' => $siteUrl,
                'path' => $installDir,
            ]
        );

        return $app;
    }

    /**
     * Generate recipe-specific config files.
     */
    protected function generateAppConfigFile(
        string $slug,
        string $dir,
        string $dbName,
        string $dbUser,
        string $dbPassword,
        string $adminEmail
    ): void {
        switch ($slug) {
            case 'laravel':
                $appKey = 'base64:' . base64_encode(random_bytes(32));
                $envContent = <<<ENV
APP_NAME=Laravel
APP_ENV=production
APP_KEY={$appKey}
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={$dbName}
DB_USERNAME={$dbUser}
DB_PASSWORD={$dbPassword}
ENV;
                File::put("{$dir}/.env", $envContent);
                File::put("{$dir}/index.php", "<?php\n/** Laravel Entry */\nrequire __DIR__.'/../vendor/autoload.php';\n");
                break;

            case 'roundcube':
            case 'nextcloud':
            case 'drupal':
            case 'joomla':
            default:
                $configContent = <<<PHP
<?php
/**
 * Configuration generated by VortanCP App Installer for {$slug}
 */
return [
    'db_name' => '{$dbName}',
    'db_user' => '{$dbUser}',
    'db_password' => '{$dbPassword}',
    'admin_email' => '{$adminEmail}',
    'installed_at' => date('c'),
];
PHP;
                File::put("{$dir}/config.php", $configContent);
                File::put("{$dir}/index.php", "<?php\n/** {$slug} index */\n");
                break;
        }
    }

    /**
     * Uninstall an application.
     */
    public function uninstall(InstalledApp $app, bool $removeFiles = true): void
    {
        $this->auditLogger->log(
            event: 'app_installer.uninstalled',
            auditable: $app,
            oldValues: ['app' => $app->app_name, 'path' => $app->install_path],
            newValues: null
        );

        if ($removeFiles && File::isDirectory($app->install_path)) {
            File::deleteDirectory($app->install_path);
        }

        $app->delete();
    }
}
