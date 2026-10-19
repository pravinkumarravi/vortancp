<?php

declare(strict_types=1);

namespace App\Modules\PhpManager\Services;

use App\Modules\PhpManager\Models\PhpPool;

class PhpFpmPoolCompilerService
{
    /**
     * Compile hardened, isolated PHP-FPM pool configuration.
     */
    public function compile(PhpPool $pool): string
    {
        $username = $pool->hostingAccount->username;
        $version = $pool->version;
        $socket = $pool->socket_path;
        $homedir = $pool->hostingAccount->homedir ?: "/home/{$username}";

        $out = [];
        $out[] = "; -------------------------------------------------------------";
        $out[] = "; VortanCP Managed PHP-FPM Pool: {$username}";
        $out[] = "; PHP Version: {$version} | Generated: " . now()->toIso8601String();
        $out[] = "; -------------------------------------------------------------\n";
        $out[] = "[{$username}]";
        $out[] = "user = {$username}";
        $out[] = "group = {$username}";
        $out[] = "listen = {$socket}";
        $out[] = "listen.owner = www-data";
        $out[] = "listen.group = www-data";
        $out[] = "listen.mode = 0660\n";

        // Process Manager directives
        $out[] = "pm = {$pool->pm_type}";
        $out[] = "pm.max_children = {$pool->pm_max_children}";
        $out[] = "pm.process_idle_timeout = 10s";
        $out[] = "pm.max_requests = 500\n";

        // Environment variables
        $out[] = "env[HOSTNAME] = \$HOSTNAME";
        $out[] = "env[PATH] = /usr/local/bin:/usr/bin:/bin";
        $out[] = "env[TMP] = /tmp";
        $out[] = "env[TMPDIR] = /tmp";
        $out[] = "env[TEMP] = /tmp\n";

        // Hardened PHP INI directives & Jailed open_basedir
        $out[] = "; --- Hardened PHP INI & Security Directives ---";
        $out[] = "php_admin_value[open_basedir] = {$homedir}:/tmp:/var/tmp";
        $out[] = "php_admin_value[memory_limit] = {$pool->memory_limit}";
        $out[] = "php_admin_value[max_execution_time] = {$pool->max_execution_time}";
        $out[] = "php_admin_value[upload_max_filesize] = {$pool->upload_max_filesize}";
        $out[] = "php_admin_value[post_max_size] = {$pool->post_max_size}";
        $out[] = "php_admin_flag[display_errors] = " . ($pool->display_errors ? 'on' : 'off');
        $out[] = "php_admin_value[session.save_path] = {$homedir}/tmp";

        return implode("\n", $out);
    }
}
