<?php

declare(strict_types=1);

namespace App\Modules\DnsCluster\Livewire;

use App\Modules\Dns\Models\DnsZone;
use App\Modules\DnsCluster\Models\DnsClusterNode;
use App\Modules\DnsCluster\Models\DnsSyncLog;
use App\Modules\DnsCluster\Services\DnsClusterSyncService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DnsClusterManager extends Component
{
    public string $name = '';
    public string $hostname = '';
    public string $ip_address = '';
    public string $sync_type = 'slave';
    public string $api_key = '';

    public bool $showAddModal = false;

    protected array $rules = [
        'name' => 'required|string|max:100',
        'hostname' => 'required|string|max:150|unique:dns_cluster_nodes,hostname',
        'ip_address' => 'required|ip',
        'sync_type' => 'required|in:master,slave,write_only,standalone',
    ];

    public function addNode(DnsClusterSyncService $syncService): void
    {
        $this->validate();

        $syncService->registerNode([
            'name' => $this->name,
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address,
            'sync_type' => $this->sync_type,
            'api_key' => $this->api_key,
        ]);

        session()->flash('success', "DNS Cluster node [{$this->hostname}] registered and synced successfully.");

        $this->reset(['name', 'hostname', 'ip_address', 'sync_type', 'api_key', 'showAddModal']);
    }

    public function pingNode(int $nodeId, DnsClusterSyncService $syncService): void
    {
        $node = DnsClusterNode::find($nodeId);
        if ($node) {
            $syncService->pingNode($node);
            session()->flash('success', "Node [{$node->hostname}] ping response: {$node->latency_ms}ms (Status: {$node->status})");
        }
    }

    public function syncNode(int $nodeId, DnsClusterSyncService $syncService): void
    {
        $node = DnsClusterNode::find($nodeId);
        if ($node) {
            $result = $syncService->syncAllZones($node);
            session()->flash('success', "Synchronized {$result['synced']} DNS zones to [{$node->hostname}].");
        }
    }

    public function deleteNode(int $nodeId): void
    {
        $node = DnsClusterNode::find($nodeId);
        if ($node) {
            $node->delete();
            session()->flash('success', 'DNS Cluster node removed successfully.');
        }
    }

    public function pingAll(DnsClusterSyncService $syncService): void
    {
        $nodes = DnsClusterNode::all();
        foreach ($nodes as $node) {
            $syncService->pingNode($node);
        }
        session()->flash('success', "Probed {$nodes->count()} cluster nodes.");
    }

    public function syncAll(DnsClusterSyncService $syncService): void
    {
        $nodes = DnsClusterNode::where('status', DnsClusterNode::STATUS_ACTIVE)->get();
        $totalSynced = 0;
        foreach ($nodes as $node) {
            $res = $syncService->syncAllZones($node);
            $totalSynced += $res['synced'];
        }
        session()->flash('success', "Cluster-wide synchronization completed. Pushed zones across {$nodes->count()} nodes.");
    }

    public function render(): View
    {
        $nodes = DnsClusterNode::latest()->get();
        $recentLogs = DnsSyncLog::with('node', 'zone')->latest('id')->limit(8)->get();

        $metrics = [
            'total_nodes' => $nodes->count(),
            'active_nodes' => $nodes->where('status', DnsClusterNode::STATUS_ACTIVE)->count(),
            'total_zones' => DnsZone::count(),
            'avg_latency' => round($nodes->where('latency_ms', '>', 0)->avg('latency_ms') ?? 0, 1),
        ];

        return view('dns_cluster::livewire.dns-cluster-manager', [
            'nodes' => $nodes,
            'recentLogs' => $recentLogs,
            'metrics' => $metrics,
        ])->layout('layouts.app', [
            'title' => 'DNS Cluster Management',
        ]);
    }
}
