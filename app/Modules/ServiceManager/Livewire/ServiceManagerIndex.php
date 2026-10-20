<?php

declare(strict_types=1);

namespace App\Modules\ServiceManager\Livewire;

use App\Modules\ServiceManager\Models\MonitoredService;
use App\Modules\ServiceManager\Services\ServiceManagerService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('Service Manager & Daemons - VortanCP')]
class ServiceManagerIndex extends Component
{
    use AuthorizesRequests;

    public string $search = '';
    public string $categoryFilter = 'all';

    public function restart(string $serviceName, ServiceManagerService $manager): void
    {
        $this->authorize('servers.manage');

        $result = $manager->controlService($serviceName, 'restart');

        if ($result['status'] === 'success') {
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function reload(string $serviceName, ServiceManagerService $manager): void
    {
        $this->authorize('servers.manage');

        $result = $manager->controlService($serviceName, 'reload');

        if ($result['status'] === 'success') {
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function start(string $serviceName, ServiceManagerService $manager): void
    {
        $this->authorize('servers.manage');

        $result = $manager->controlService($serviceName, 'start');

        if ($result['status'] === 'success') {
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function stop(string $serviceName, ServiceManagerService $manager): void
    {
        $this->authorize('servers.manage');

        $result = $manager->controlService($serviceName, 'stop');

        if ($result['status'] === 'success') {
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function toggleMonitoring(int $id, ServiceManagerService $manager): void
    {
        $this->authorize('servers.manage');

        $service = $manager->toggleMonitoring($id);
        $state = $service->is_monitored ? 'enabled' : 'disabled';
        session()->flash('success', "Watchdog monitoring {$state} for [{$service->display_name}].");
    }

    public function toggleAutoRestart(int $id, ServiceManagerService $manager): void
    {
        $this->authorize('servers.manage');

        $service = $manager->toggleAutoRestart($id);
        $state = $service->auto_restart ? 'enabled' : 'disabled';
        session()->flash('success', "Auto-restart crash recovery {$state} for [{$service->display_name}].");
    }

    public function triggerWatchdog(ServiceManagerService $manager): void
    {
        $this->authorize('servers.manage');

        $result = $manager->runWatchdog();

        if ($result['healed_count'] > 0) {
            session()->flash('success', "Watchdog probe complete: Auto-healed ({$result['healed_count']}) services: " . implode(', ', $result['healed_services']));
        } else {
            session()->flash('success', "Watchdog probe complete: All ({$result['healthy_count']}) services operational.");
        }
    }

    public function render(ServiceManagerService $manager): View
    {
        $services = $manager->getServices($this->search, $this->categoryFilter);

        $runningCount = $services->where('status', MonitoredService::STATUS_RUNNING)->count();
        $stoppedCount = $services->where('status', MonitoredService::STATUS_STOPPED)->count();
        $failedCount = $services->where('status', MonitoredService::STATUS_FAILED)->count();

        return view('service_manager::livewire.service-manager-index', [
            'services' => $services,
            'runningCount' => $runningCount,
            'stoppedCount' => $stoppedCount,
            'failedCount' => $failedCount,
        ]);
    }
}
