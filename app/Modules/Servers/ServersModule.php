<?php

declare(strict_types=1);

namespace App\Modules\Servers;

use App\Modules\Core\AbstractModule;

class ServersModule extends AbstractModule
{
    protected string $id = 'servers';
    protected string $name = 'Server Inventory & Node Management';
    protected string $version = '1.0.0';
    protected string $description = 'Register, monitor, and execute privileged infrastructure operations across local and remote server nodes.';
    protected int $priority = 5;
    protected array $dependencies = ['core', 'identity', 'audit'];
}
