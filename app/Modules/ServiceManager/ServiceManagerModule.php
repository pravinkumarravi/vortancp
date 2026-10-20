<?php

declare(strict_types=1);

namespace App\Modules\ServiceManager;

use App\Modules\Core\AbstractModule;

class ServiceManagerModule extends AbstractModule
{
    protected string $id = 'service_manager';
    protected string $name = 'System Service Manager & Daemon Watchdog';
    protected string $version = '1.0.0';
    protected string $description = 'Core systemd daemon monitoring, real-time status inspection, 1-click service control (restart/reload/start/stop), and automated crash recovery watchdog.';
    protected int $priority = 27;
    protected array $dependencies = ['core', 'identity', 'servers', 'agent'];
}
