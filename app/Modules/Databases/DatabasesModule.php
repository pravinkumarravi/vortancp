<?php

declare(strict_types=1);

namespace App\Modules\Databases;

use App\Modules\Core\AbstractModule;

class DatabasesModule extends AbstractModule
{
    protected string $id = 'databases';
    protected string $name = 'Databases & Users';
    protected string $version = '1.0.0';
    protected string $description = 'Relational database orchestration, MySQL/MariaDB/PostgreSQL user provisioning, remote host controls, and privilege grants.';
    protected int $priority = 14;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'hosting_plans'];
}
