<?php

declare(strict_types=1);

namespace App\Modules\ServiceManager\Services;

use App\Modules\Agent\Services\AgentClientService;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\ServiceManager\Models\MonitoredService;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Throwable;

class ServiceManagerService
{
    public const ALLOWED_ACTIONS = ['restart', 'reload', 'start', 'stop'];

    public function __construct(
        protected AgentClientService $agentClient,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Get all monitored system services with optional search and category filters.
     *
     * @return Collection<int, MonitoredService>
     */
    public function getServices(string $search = '', string $category = 'all'): Collection
    {
        if (MonitoredService::count() === 0) {
            MonitoredService::seedDefaults();
        }

        return MonitoredService::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category !== 'all', function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Dispatch a control action (restart, reload, start, stop) to a service.
     *
     * @param string $serviceName
     * @param string $action
     * @return array<string, mixed>
     */
    public function controlService(string $serviceName, string $action): array
    {
        $action = strtolower(trim($action));
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            throw new InvalidArgumentException("Action [{$action}] is not permitted.");
        }

        $service = MonitoredService::where('name', $serviceName)->first();
        if (!$service) {
            throw new InvalidArgumentException("Monitored service [{$serviceName}] not found.");
        }

        try {
            $response = $this->agentClient->call('system.service_action', [
                'service' => $serviceName,
                'action' => $action,
            ]);

            // Update database state
            if ($action === 'stop') {
                $service->status = MonitoredService::STATUS_STOPPED;
            } else {
                $service->status = MonitoredService::STATUS_RUNNING;
            }

            if ($action === 'restart' || $action === 'reload') {
                $service->last_restarted_at = now();
                $service->restart_count++;
            }

            $service->last_checked_at = now();
            $service->save();

            $this->auditLogger->log(
                event: 'service.controlled',
                auditable: $service,
                newValues: [
                    'service' => $serviceName,
                    'action' => $action,
                    'status' => $service->status,
                ]
            );

            return [
                'status' => 'success',
                'message' => "Service [{$service->display_name}] {$action}ed successfully.",
                'data' => $service->fresh()->toArray(),
            ];
        } catch (Throwable $e) {
            $service->update([
                'status' => MonitoredService::STATUS_FAILED,
                'last_checked_at' => now(),
            ]);

            return [
                'status' => 'error',
                'message' => "Failed to {$action} service [{$serviceName}]: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle watchdog monitoring for a service.
     */
    public function toggleMonitoring(int $serviceId): MonitoredService
    {
        $service = MonitoredService::findOrFail($serviceId);
        $service->update(['is_monitored' => !$service->is_monitored]);

        $this->auditLogger->log(
            event: 'service.monitoring_toggled',
            auditable: $service,
            newValues: ['is_monitored' => $service->is_monitored]
        );

        return $service;
    }

    /**
     * Toggle auto-restart crash recovery for a service.
     */
    public function toggleAutoRestart(int $serviceId): MonitoredService
    {
        $service = MonitoredService::findOrFail($serviceId);
        $service->update(['auto_restart' => !$service->auto_restart]);

        $this->auditLogger->log(
            event: 'service.auto_restart_toggled',
            auditable: $service,
            newValues: ['auto_restart' => $service->auto_restart]
        );

        return $service;
    }

    /**
     * Run watchdog crash recovery across all monitored daemons.
     *
     * @return array<string, mixed>
     */
    public function runWatchdog(): array
    {
        if (MonitoredService::count() === 0) {
            MonitoredService::seedDefaults();
        }

        $monitored = MonitoredService::where('is_monitored', true)->get();
        $healed = [];
        $healthy = 0;

        foreach ($monitored as $service) {
            $service->update(['last_checked_at' => now()]);

            // If service marked as stopped or failed, and auto_restart is enabled, attempt orderly recovery
            if (($service->status === MonitoredService::STATUS_STOPPED || $service->status === MonitoredService::STATUS_FAILED) && $service->auto_restart) {
                $recovery = $this->controlService($service->name, 'restart');
                if ($recovery['status'] === 'success') {
                    $healed[] = $service->name;

                    $this->auditLogger->log(
                        event: 'service.watchdog_auto_healed',
                        auditable: $service,
                        newValues: ['service' => $service->name, 'action' => 'watchdog_auto_restart']
                    );
                }
            } else {
                $healthy++;
            }
        }

        return [
            'total_checked' => $monitored->count(),
            'healthy_count' => $healthy,
            'healed_count' => count($healed),
            'healed_services' => $healed,
        ];
    }
}
