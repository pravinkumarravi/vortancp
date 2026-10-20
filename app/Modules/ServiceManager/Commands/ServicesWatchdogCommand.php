<?php

declare(strict_types=1);

namespace App\Modules\ServiceManager\Commands;

use App\Modules\ServiceManager\Services\ServiceManagerService;
use Illuminate\Console\Command;

class ServicesWatchdogCommand extends Command
{
    protected $signature = 'vortan:services-watchdog';
    protected $description = 'Heartbeat watchdog probing and auto-healing crashed or inactive core system services';

    public function handle(ServiceManagerService $serviceManager): int
    {
        $this->info('Executing VortanCP Service Watchdog check...');

        $result = $serviceManager->runWatchdog();

        $this->line("Total monitored services checked: {$result['total_checked']}");
        $this->line("Healthy services: {$result['healthy_count']}");

        if ($result['healed_count'] > 0) {
            $this->warn("Auto-healed services ({$result['healed_count']}): " . implode(', ', $result['healed_services']));
        } else {
            $this->info('All monitored services are operational.');
        }

        return self::SUCCESS;
    }
}
