<?php

declare(strict_types=1);

namespace Tests\Unit\Metrics;

use App\Modules\Metrics\Services\SystemMetricsCollectorService;
use Tests\TestCase;

class MetricsCollectorTest extends TestCase
{
    protected SystemMetricsCollectorService $collector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = new SystemMetricsCollectorService();
    }

    public function test_captures_live_system_metrics(): void
    {
        $stats = $this->collector->captureLiveStats();

        $this->assertArrayHasKey('cpu_pct', $stats);
        $this->assertArrayHasKey('memory_pct', $stats);
        $this->assertArrayHasKey('disk_pct', $stats);
        $this->assertArrayHasKey('disk_used_mb', $stats);
        $this->assertArrayHasKey('load_1min', $stats);

        $this->assertGreaterThanOrEqual(0, $stats['cpu_pct']);
        $this->assertLessThanOrEqual(100, $stats['cpu_pct']);
        $this->assertGreaterThanOrEqual(0, $stats['disk_pct']);
        $this->assertLessThanOrEqual(100, $stats['disk_pct']);
    }
}
