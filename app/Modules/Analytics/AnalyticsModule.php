<?php

declare(strict_types=1);

namespace App\Modules\Analytics;

use App\Modules\Core\AbstractModule;

class AnalyticsModule extends AbstractModule
{
    protected string $id = 'analytics';
    protected string $name = 'Visitor & HTTP Traffic Analytics';
    protected string $version = '1.0.0';
    protected string $description = 'Real-time HTTP access log analytics, bandwidth utilization tracking, top paths, unique visitor counts, and status code distribution.';
    protected int $priority = 32;
    protected array $dependencies = ['core', 'identity', 'servers', 'domains', 'accounts'];
}
