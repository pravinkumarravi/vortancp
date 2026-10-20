<?php

declare(strict_types=1);

namespace App\Modules\Logs;

use App\Modules\Core\AbstractModule;

class LogsModule extends AbstractModule
{
    protected string $id = 'logs';
    protected string $name = 'Server & Domain Log Inspector';
    protected string $version = '1.0.0';
    protected string $description = 'Real-time server log inspection, Nginx access/error logs, PHP-FPM, database, mail logs, and customer self-service domain log browser with live filtering.';
    protected int $priority = 28;
    protected array $dependencies = ['core', 'identity', 'servers', 'domains', 'agent'];
}
