<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter;

use App\Modules\Core\AbstractModule;

class SecurityCenterModule extends AbstractModule
{
    protected string $id = 'security_center';
    protected string $name = 'Security Center & WAF';
    protected string $version = '1.0.0';
    protected string $description = 'ModSecurity Web Application Firewall, OWASP Core Rule Set, malware signature scanner, and server hardening advisor.';
    protected int $priority = 20;
    protected array $dependencies = ['core', 'identity', 'servers', 'domains'];
}
