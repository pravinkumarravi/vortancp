<?php

declare(strict_types=1);

namespace App\Modules\DnsCluster;

use App\Modules\Core\AbstractModule;

class DnsClusterModule extends AbstractModule
{
    protected string $id = 'dns_cluster';
    protected string $name = 'DNS Cluster & Remote Nameserver Sync';
    protected string $version = '1.0.0';
    protected string $description = 'Centralized multi-node DNS clustering, automated master-slave zone synchronization, BIND/PowerDNS replication, and edge nameserver health monitoring.';
    protected int $priority = 31;
    protected array $dependencies = ['core', 'identity', 'servers', 'dns'];
}
