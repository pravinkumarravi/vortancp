<?php

declare(strict_types=1);

namespace App\Modules\WordPressToolkit\Services;

use Illuminate\Support\Str;

class WordPressConfigCompilerService
{
    /**
     * Generate standard production-grade wp-config.php content with hardened salts and directives.
     */
    public function compileWpConfig(
        string $dbName,
        string $dbUser,
        string $dbPassword,
        string $dbHost = '127.0.0.1',
        string $tablePrefix = 'wp_',
        bool $disallowFileEdit = true,
        bool $debug = false,
        bool $autoUpdateCore = true
    ): string {
        $keys = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
        ];

        $saltsLines = [];
        foreach ($keys as $key) {
            $saltVal = addcslashes(Str::random(64), "'\\");
            $saltsLines[] = "define('{$key}', '{$saltVal}');";
        }
        $saltsBlock = implode("\n", $saltsLines);

        $disallowEditStr = $disallowFileEdit ? 'true' : 'false';
        $debugStr = $debug ? 'true' : 'false';
        $autoUpdateStr = $autoUpdateCore ? 'true' : 'false';

        return <<<PHP
<?php
/**
 * WordPress Base Configuration generated automatically by VortanCP WP Toolkit.
 * Database: {$dbName}
 */

// ** Database settings - You can get this info from your web host ** //
define('DB_NAME', '{$dbName}');
define('DB_USER', '{$dbUser}');
define('DB_PASSWORD', '{$dbPassword}');
define('DB_HOST', '{$dbHost}');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

/**#@+
 * Authentication unique keys and salts.
 */
{$saltsBlock}
/**#@-*/

/**
 * WordPress database table prefix.
 */
\$table_prefix = '{$tablePrefix}';

/**
 * Hardening and Security Directives
 */
define('DISALLOW_FILE_EDIT', {$disallowEditStr});
define('WP_DEBUG', {$debugStr});
define('WP_AUTO_UPDATE_CORE', {$autoUpdateStr});

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

PHP;
    }

    /**
     * Generate .maintenance file content.
     */
    public function compileMaintenanceFile(): string
    {
        return '<?php $upgrading = ' . time() . '; ?>' . "\n";
    }
}
