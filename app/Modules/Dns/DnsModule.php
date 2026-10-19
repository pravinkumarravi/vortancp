<?php

declare(strict_types=1);

namespace App\Modules\Dns;

use App\Modules\Core\AbstractModule;

class DnsModule extends AbstractModule
{
    protected string $id = 'dns';
    protected string $name = 'DNS Management';
    protected string $version = '1.0.0';
    protected string $description = 'Authoritative DNS zone management, resource record CRUD, RFC 1035 BIND zone compiler, and PowerDNS/BIND9 synchronization.';
    protected int $priority = 11;
    protected array $dependencies = ['core', 'identity', 'servers', 'domains'];
}
