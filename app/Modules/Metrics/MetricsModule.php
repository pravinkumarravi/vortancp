<?php

declare(strict_types=1);

namespace App\Modules\Metrics;

use App\Modules\Core\AbstractModule;

class MetricsModule extends AbstractModule
{
    protected string $id = 'metrics';
    protected string $name = 'Metrics & Resource Utilization';
    protected string $version = '1.0.0';
    protected string $description = 'Hardware monitoring, CPU/RAM telemetry, load averages, and account-level bandwidth/disk quota utilization tracking.';
    protected int $priority = 27;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'hosting_plans'];
}
