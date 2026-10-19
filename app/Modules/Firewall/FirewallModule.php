<?php

declare(strict_types=1);

namespace App\Modules\Firewall;

use App\Modules\Core\AbstractModule;

class FirewallModule extends AbstractModule
{
    protected string $id = 'firewall';
    protected string $name = 'Firewall & Port Management';
    protected string $version = '1.0.0';
    protected string $description = 'Host firewall management, UFW/IPTables compiler, standard web hosting port profiles, and automated Fail2ban intrusion IP bans.';
    protected int $priority = 19;
    protected array $dependencies = ['core', 'identity', 'servers'];
}
