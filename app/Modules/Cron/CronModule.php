<?php

declare(strict_types=1);

namespace App\Modules\Cron;

use App\Modules\Core\AbstractModule;

class CronModule extends AbstractModule
{
    protected string $id = 'cron';
    protected string $name = 'Cron Jobs & Task Scheduler';
    protected string $version = '1.0.0';
    protected string $description = 'Automated scheduled tasks, Unix crontab file compiler, command injection sanitization, timing presets, and quota controls.';
    protected int $priority = 17;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'hosting_plans'];
}
