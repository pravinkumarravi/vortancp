<?php

declare(strict_types=1);

namespace App\Modules\Backups\Models;

namespace App\Modules\Backups;

use App\Modules\Core\AbstractModule;

class BackupModule extends AbstractModule
{
    protected string $id = 'backups';
    protected string $name = 'Backup & Disaster Recovery';
    protected string $version = '1.0.0';
    protected string $description = 'Account snapshot archives, SHA-256 integrity checks, selective restore engine, and automated retention policies.';
    protected int $priority = 18;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'hosting_plans'];
}
