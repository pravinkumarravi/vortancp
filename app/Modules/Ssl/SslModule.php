<?php

declare(strict_types=1);

namespace App\Modules\Ssl;

use App\Modules\Core\AbstractModule;

class SslModule extends AbstractModule
{
    protected string $id = 'ssl';
    protected string $name = 'SSL/TLS Management';
    protected string $version = '1.0.0';
    protected string $description = 'Automated ACME Let\'s Encrypt issuance, ZeroSSL, commercial certificate installation, X.509 verification, and auto-renewal.';
    protected int $priority = 12;
    protected array $dependencies = ['core', 'identity', 'servers', 'domains', 'hosting_plans'];
}
