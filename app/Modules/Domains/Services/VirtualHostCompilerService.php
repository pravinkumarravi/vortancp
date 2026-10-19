<?php

declare(strict_types=1);

namespace App\Modules\Domains\Services;

use App\Modules\Domains\Models\Domain;

class VirtualHostCompilerService
{
    /**
     * Generate an optimized, hardened Nginx server block configuration.
     */
    public function compileNginx(Domain $domain, bool $hasSsl = false, ?string $certPath = null, ?string $keyPath = null): string
    {
        $domainName = $domain->domain;
        $username = $domain->hostingAccount->username;
        $docRoot = rtrim($domain->document_root, '/');
        $phpVersion = $domain->php_version;
        $socketPath = "/run/php/php{$phpVersion}-fpm.sock";
        $logDir = "/var/log/vortan/{$username}";

        $serverNames = [$domainName];
        if (!str_starts_with($domainName, 'www.') && !$domain->isSubdomain()) {
            $serverNames[] = "www.{$domainName}";
        }
        $serverNameStr = implode(' ', $serverNames);

        $out = [];
        $out[] = "# -------------------------------------------------------------";
        $out[] = "# VortanCP Managed Nginx Virtual Host: {$domainName}";
        $out[] = "# Account: {$username} | PHP: {$phpVersion} | Generated: " . now()->toIso8601String();
        $out[] = "# -------------------------------------------------------------\n";

        // HTTP Block (Port 80)
        if ($domain->force_https && $hasSsl) {
            $out[] = "server {";
            $out[] = "    listen 80;";
            $out[] = "    listen [::]:80;";
            $out[] = "    server_name {$serverNameStr};";
            $out[] = "    return 301 https://\$host\$request_uri;";
            $out[] = "}\n";
        }

        // Main Server Block
        $out[] = "server {";
        if ($hasSsl && $certPath && $keyPath) {
            $out[] = "    listen 443 ssl http2;";
            $out[] = "    listen [::]:443 ssl http2;";
            $out[] = "    ssl_certificate {$certPath};";
            $out[] = "    ssl_certificate_key {$keyPath};";
            $out[] = "    ssl_protocols TLSv1.2 TLSv1.3;";
            $out[] = "    ssl_ciphers HIGH:!aNULL:!MD5;";
            $out[] = "    ssl_prefer_server_ciphers on;";
        } else {
            $out[] = "    listen 80;";
            $out[] = "    listen [::]:80;";
        }

        $out[] = "    server_name {$serverNameStr};";
        $out[] = "    root {$docRoot};";
        $out[] = "    index index.php index.html index.htm;\n";

        // Security headers
        $out[] = "    add_header X-Frame-Options \"SAMEORIGIN\" always;";
        $out[] = "    add_header X-Content-Type-Options \"nosniff\" always;";
        $out[] = "    add_header X-XSS-Protection \"1; mode=block\" always;\n";

        // Logging
        $out[] = "    access_log {$logDir}/{$domainName}.access.log combined;";
        $out[] = "    error_log {$logDir}/{$domainName}.error.log error;\n";

        // Standard try_files
        $out[] = "    location / {";
        $out[] = "        try_files \$uri \$uri/ /index.php?\$query_string;";
        $out[] = "    }\n";

        // PHP-FPM FastCGI Handler
        $out[] = "    location ~ \.php$ {";
        $out[] = "        include fastcgi_params;";
        $out[] = "        fastcgi_pass unix:{$socketPath};";
        $out[] = "        fastcgi_index index.php;";
        $out[] = "        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;";
        $out[] = "        fastcgi_param DOCUMENT_ROOT \$realpath_root;";
        $out[] = "        fastcgi_buffer_size 16k;";
        $out[] = "        fastcgi_buffers 4 16k;";
        $out[] = "    }\n";

        // Protect hidden files and version control
        $out[] = "    location ~ /\.(?!well-known).* {";
        $out[] = "        deny all;";
        $out[] = "    }";

        $out[] = "}\n";

        return implode("\n", $out);
    }
}
