<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Metrics\Models\AccountMetric;
use App\Modules\Metrics\Models\ServerMetric;
use App\Modules\Metrics\Services\AccountMetricsAggregatorService;
use App\Modules\Metrics\Services\SystemMetricsCollectorService;
use App\Modules\Servers\Models\Server;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MetricsIndex extends Component
{
    public ?int $selectedAccountId = null;
    public ?int $selectedServerId = null;

    public function mount(?int $accountId = null): void
    {
        $user = Auth::user();

        if ($accountId) {
            $this->selectedAccountId = $accountId;
        } else {
            $query = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE);
            if ($user && $user->hasRole('customer')) {
                $query->whereHas('customer', fn($q) => $q->where('user_id', $user->id));
            }
            $this->selectedAccountId = $query->first()?->id;
        }

        $this->selectedServerId = Server::where('status', Server::STATUS_ACTIVE)->first()?->id;
    }

    public function getAccountProperty(): ?HostingAccount
    {
        if (! $this->selectedAccountId) {
            return null;
        }

        return HostingAccount::with(['customer', 'server', 'subscription.hostingPlan'])->find($this->selectedAccountId);
    }

    public function getServerProperty(): ?Server
    {
        if (! $this->selectedServerId) {
            return null;
        }

        return Server::find($this->selectedServerId);
    }

    public function probeMetrics(SystemMetricsCollectorService $collector): void
    {
        $server = $this->server;
        if ($server) {
            $collector->recordServerMetric($server);
            session()->flash('success', "Live node telemetry collected for [{$server->name}].");
        }
    }

    public function render(SystemMetricsCollectorService $systemCollector, AccountMetricsAggregatorService $accountAggregator)
    {
        $user = Auth::user();
        $isAdmin = ! ($user && $user->hasRole('customer'));

        $accounts = $isAdmin
            ? HostingAccount::select('id', 'username', 'primary_domain')->get()
            : [];

        $servers = $isAdmin
            ? Server::select('id', 'name', 'hostname', 'ip_address')->get()
            : [];

        $liveNodeStats = $systemCollector->captureLiveStats();

        $accountReport = $this->account
            ? $accountAggregator->getUsageReport($this->account)
            : null;

        $serverHistory = $this->server
            ? ServerMetric::where('server_id', $this->server->id)->latest('recorded_at')->take(10)->get()
            : collect();

        $accountHistory = $this->account
            ? AccountMetric::where('hosting_account_id', $this->account->id)->latest('recorded_at')->take(10)->get()
            : collect();

        return view('metrics::livewire.metrics-index', [
            'isAdmin' => $isAdmin,
            'accounts' => $accounts,
            'servers' => $servers,
            'account' => $this->account,
            'server' => $this->server,
            'liveNodeStats' => $liveNodeStats,
            'accountReport' => $accountReport,
            'serverHistory' => $serverHistory,
            'accountHistory' => $accountHistory,
        ]);
    }
}
