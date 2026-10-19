<?php

declare(strict_types=1);

namespace App\Modules\Servers\Livewire;

use App\Modules\Servers\Models\Server;
use App\Modules\Servers\Services\ServerNodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('Server Inventory - VortanCP')]
class ServerIndex extends Component
{
    use AuthorizesRequests;

    public string $search = '';
    public string $statusFilter = 'all';

    // Register Modal State
    public bool $showRegisterModal = false;
    public string $nodeName = '';
    public string $nodeHostname = '';
    public string $nodeIp = '';
    public int $nodePort = 8443;
    public string $nodeConnectionType = Server::CONNECTION_LOCAL;
    public string $nodeAuthToken = '';

    protected function rules(): array
    {
        return [
            'nodeName' => ['required', 'string', 'max:100'],
            'nodeHostname' => ['required', 'string', 'max:255', 'unique:servers,hostname'],
            'nodeIp' => ['required', 'string', 'max:45'],
            'nodePort' => ['required', 'integer', 'between:1,65535'],
            'nodeConnectionType' => ['required', 'in:local_socket,remote_mtls'],
            'nodeAuthToken' => ['nullable', 'string'],
        ];
    }

    public function syncHealth(int $serverId): void
    {
        $server = Server::findOrFail($serverId);
        $this->authorize('servers.manage');

        app(ServerNodeService::class)->syncServerHealth($server);

        session()->flash('success', "Node [{$server->name}] telemetry synchronized successfully.");
    }

    public function toggleMaintenance(int $serverId): void
    {
        $server = Server::findOrFail($serverId);
        $this->authorize('servers.manage');

        $newStatus = $server->status === Server::STATUS_MAINTENANCE 
            ? Server::STATUS_ACTIVE 
            : Server::STATUS_MAINTENANCE;

        $server->update(['status' => $newStatus]);

        session()->flash('success', "Node [{$server->name}] status changed to {$newStatus}.");
    }

    public function registerNode(): void
    {
        $this->authorize('servers.manage');
        $this->validate();

        $service = app(ServerNodeService::class);
        $service->registerServer([
            'name' => $this->nodeName,
            'hostname' => $this->nodeHostname,
            'ip_address' => $this->nodeIp,
            'port' => $this->nodePort,
            'connection_type' => $this->nodeConnectionType,
            'auth_token' => $this->nodeAuthToken ?: null,
            'status' => Server::STATUS_ACTIVE,
        ]);

        $this->reset(['nodeName', 'nodeHostname', 'nodeIp', 'nodePort', 'nodeAuthToken', 'showRegisterModal']);
        session()->flash('success', 'New server node registered and synchronized successfully.');
    }

    public function render(): View
    {
        $servers = Server::query()
            ->with('capabilities')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('hostname', 'like', "%{$this->search}%")
                      ->orWhere('ip_address', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('id', 'asc')
            ->get();

        return view('servers::livewire.server-index', [
            'servers' => $servers,
        ]);
    }
}
