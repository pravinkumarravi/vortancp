<?php

declare(strict_types=1);

namespace App\Modules\Migration;

use App\Modules\Core\AbstractModule;

class MigrationModule extends AbstractModule
{
    protected string $id = 'migration';
    protected string $name = 'Server Migration & Archive Importer';
    protected string $version = '1.0.0';
    protected string $description = 'Automated server-to-server migration engine, cPanel cpmove archive importer, Plesk backup decoder, and comprehensive asset restorer.';
    protected int $priority = 30;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'hosting_plans', 'domains', 'databases', 'email'];
}
