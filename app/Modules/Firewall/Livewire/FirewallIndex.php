<?php

declare(strict_types=1);

namespace App\Modules\Firewall\Livewire;

use App\Modules\Firewall\Models\BannedIp;
use App\Modules\Firewall\Models\FirewallRule;
use App\Modules\Firewall\Services\FirewallManagerService;
use App\Modules\Firewall\Services\FirewallRuleCompilerService;
use App\Modules\Servers\Models\Server;
use Livewire\Component;

class FirewallIndex extends Component
{
    public ?int $selectedServerId = null;
    public string $activeTab = 'rules'; // 'rules', 'banned_ips', 'preview'

    // Create Rule Modal
    public bool $showCreateRuleModal = false;
    public string $direction = FirewallRule::DIRECTION_INBOUND;
    public string $action = FirewallRule::ACTION_ALLOW;
    public string $protocol = FirewallRule::PROTO_TCP;
    public string $port = '80';
    public string $source_ip = '0.0.0.0/0';
    public string $comment = '';
    public int $priority = 100;

    // Ban IP Modal
    public bool $showBanModal = false;
    public string $ban_ip = '';
    public string $ban_reason = '';
    public int $ban_hours = 24;

    public function mount(?int $serverId = null): void
    {
        $this->selectedServerId = $serverId ?: Server::first()?->id;
    }

    public function getServerProperty(): ?Server
    {
        if (! $this->selectedServerId) {
            return null;
        }

        return Server::with(['firewallRules', 'bannedIps'])->find($this->selectedServerId);
    }

    public function createRule(FirewallManagerService $fwService): void
    {
        $this->validate([
            'port' => 'required|string',
            'protocol' => 'required|in:tcp,udp,any',
            'action' => 'required|in:allow,deny,reject,limit',
            'source_ip' => 'required|string',
            'comment' => 'nullable|string|max:255',
            'priority' => 'required|integer|min:1|max:999',
        ]);

        $server = $this->server;
        if (! $server) {
            session()->flash('error', 'No target server selected.');
            return;
        }

        try {
            $fwService->createRule(
                server: $server,
                port: $this->port,
                protocol: $this->protocol,
                action: $this->action,
                sourceIp: $this->source_ip,
                direction: $this->direction,
                comment: $this->comment ?: null,
                priority: $this->priority
            );

            session()->flash('success', "Firewall rule for port [{$this->port}/{$this->protocol}] added successfully.");
            $this->reset(['port', 'comment', 'showCreateRuleModal']);
            $this->port = '80';
            $this->source_ip = '0.0.0.0/0';
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleRule(int $ruleId, FirewallManagerService $fwService): void
    {
        $rule = FirewallRule::where('server_id', $this->selectedServerId)->find($ruleId);
        if ($rule) {
            $fwService->toggleRule($rule);
            session()->flash('success', "Rule state updated.");
        }
    }

    public function deleteRule(int $ruleId, FirewallManagerService $fwService): void
    {
        $rule = FirewallRule::where('server_id', $this->selectedServerId)->find($ruleId);
        if ($rule) {
            $fwService->deleteRule($rule);
            session()->flash('success', "Rule deleted.");
        }
    }

    public function applyHostingProfile(FirewallManagerService $fwService): void
    {
        $server = $this->server;
        if (! $server) {
            session()->flash('error', 'No server selected.');
            return;
        }

        $added = $fwService->applyStandardHostingProfile($server);
        session()->flash('success', "Applied standard web hosting firewall profile ({$added} new rules added).");
    }

    public function banIp(FirewallManagerService $fwService): void
    {
        $this->validate([
            'ban_ip' => 'required|ip',
            'ban_reason' => 'nullable|string|max:255',
            'ban_hours' => 'required|integer|min:1',
        ]);

        $server = $this->server;
        if (! $server) {
            session()->flash('error', 'No server selected.');
            return;
        }

        try {
            $fwService->banIp(
                server: $server,
                ip: $this->ban_ip,
                jail: 'manual',
                reason: $this->ban_reason ?: 'Administrative block',
                durationHours: $this->ban_hours
            );

            session()->flash('success', "IP [{$this->ban_ip}] banned successfully.");
            $this->reset(['ban_ip', 'ban_reason', 'showBanModal']);
            $this->ban_hours = 24;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function unbanIp(int $bannedId, FirewallManagerService $fwService): void
    {
        $banned = BannedIp::where('server_id', $this->selectedServerId)->find($bannedId);
        if ($banned) {
            $fwService->unbanIp($banned);
            session()->flash('success', "IP [{$banned->ip_address}] unbanned.");
        }
    }

    public function render(FirewallRuleCompilerService $compiler)
    {
        $servers = Server::select('id', 'name', 'hostname', 'ip_address')->get();

        $rules = $this->server
            ? $this->server->firewallRules()->orderBy('priority')->get()
            : collect();

        $bannedIps = $this->server
            ? $this->server->bannedIps()->latest()->get()
            : collect();

        $iptablesScript = $this->server && $this->activeTab === 'preview'
            ? $compiler->compileIptablesScript($this->server)
            : '';

        $ufwCommands = $this->server && $this->activeTab === 'preview'
            ? $compiler->compileUfwCommands($this->server)
            : [];

        return view('firewall::livewire.firewall-index', [
            'servers' => $servers,
            'server' => $this->server,
            'rules' => $rules,
            'bannedIps' => $bannedIps,
            'iptablesScript' => $iptablesScript,
            'ufwCommands' => $ufwCommands,
        ]);
    }
}
