<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Livewire;

use App\Modules\Domains\Models\Domain;
use App\Modules\SecurityCenter\Models\QuarantinedFile;
use App\Modules\SecurityCenter\Models\SecurityScan;
use App\Modules\SecurityCenter\Models\WafConfiguration;
use App\Modules\SecurityCenter\Services\ModSecurityCompilerService;
use App\Modules\SecurityCenter\Services\SecurityAdvisorService;
use App\Modules\SecurityCenter\Services\SecurityScannerService;
use App\Modules\Servers\Models\Server;
use Livewire\Component;

class SecurityCenterIndex extends Component
{
    public ?int $selectedServerId = null;
    public string $activeTab = 'advisor'; // advisor, waf, scanner, quarantine

    // WAF Tab
    public ?int $selectedDomainId = null;
    public bool $waf_is_enabled = true;
    public int $paranoia_level = 1;
    public bool $block_sqli = true;
    public bool $block_xss = true;
    public bool $block_rfi = true;
    public bool $block_code_injection = true;
    public int $anomaly_threshold = 5;
    public string $disabled_rule_ids_input = '';
    public string $custom_rules = '';
    public string $compiledWafConfig = '';

    // Scanner Tab
    public string $scan_path = '';

    public function mount(?int $serverId = null): void
    {
        $this->selectedServerId = $serverId ?: Server::first()?->id;
        $this->selectedDomainId = Domain::first()?->id;
        $this->loadWafSettings();
    }

    public function getServerProperty(): ?Server
    {
        if (! $this->selectedServerId) {
            return null;
        }

        return Server::find($this->selectedServerId);
    }

    public function loadWafSettings(): void
    {
        if (! $this->selectedServerId) {
            return;
        }

        $config = WafConfiguration::where('server_id', $this->selectedServerId)
            ->where('domain_id', $this->selectedDomainId)
            ->first();

        if ($config) {
            $this->waf_is_enabled = $config->is_enabled;
            $this->paranoia_level = $config->paranoia_level;
            $this->block_sqli = $config->block_sqli;
            $this->block_xss = $config->block_xss;
            $this->block_rfi = $config->block_rfi;
            $this->block_code_injection = $config->block_code_injection;
            $this->anomaly_threshold = $config->anomaly_threshold;
            $this->disabled_rule_ids_input = implode(', ', $config->disabled_rule_ids ?? []);
            $this->custom_rules = $config->custom_rules ?? '';

            $compiler = app(ModSecurityCompilerService::class);
            $this->compiledWafConfig = $compiler->compileSnippet($config);
        } else {
            $this->waf_is_enabled = true;
            $this->paranoia_level = 1;
            $this->block_sqli = true;
            $this->block_xss = true;
            $this->block_rfi = true;
            $this->block_code_injection = true;
            $this->anomaly_threshold = 5;
            $this->disabled_rule_ids_input = '';
            $this->custom_rules = '';
            $this->compiledWafConfig = '';
        }
    }

    public function updatedSelectedDomainId(): void
    {
        $this->loadWafSettings();
    }

    public function saveWafSettings(ModSecurityCompilerService $compiler): void
    {
        $server = $this->server;
        if (! $server) {
            session()->flash('error', 'No server selected.');
            return;
        }

        $disabledIds = array_filter(array_map('intval', explode(',', $this->disabled_rule_ids_input)));

        $config = WafConfiguration::updateOrCreate(
            ['server_id' => $server->id, 'domain_id' => $this->selectedDomainId],
            [
                'is_enabled' => $this->waf_is_enabled,
                'paranoia_level' => $this->paranoia_level,
                'block_sqli' => $this->block_sqli,
                'block_xss' => $this->block_xss,
                'block_rfi' => $this->block_rfi,
                'block_code_injection' => $this->block_code_injection,
                'anomaly_threshold' => $this->anomaly_threshold,
                'disabled_rule_ids' => array_values($disabledIds),
                'custom_rules' => $this->custom_rules ?: null,
            ]
        );

        $this->compiledWafConfig = $compiler->compileSnippet($config);
        session()->flash('success', 'ModSecurity WAF policy saved and recompiled successfully.');
    }

    public function runScan(SecurityScannerService $scannerService): void
    {
        $server = $this->server;
        if (! $server) {
            session()->flash('error', 'No server selected.');
            return;
        }

        $target = trim($this->scan_path);
        if ($target === '') {
            $target = storage_path('sandboxes');
        }

        try {
            $scan = $scannerService->scanDirectory($server, null, $target);
            session()->flash('success', "Scan completed: {$scan->scanned_files_count} files inspected, {$scan->infected_files_count} threats detected.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function restoreQuarantine(int $id, SecurityScannerService $scannerService): void
    {
        $quarantined = QuarantinedFile::find($id);
        if ($quarantined) {
            try {
                $scannerService->restoreFile($quarantined);
                session()->flash('success', "File restored to {$quarantined->original_path}.");
            } catch (\Exception $e) {
                session()->flash('error', $e->getMessage());
            }
        }
    }

    public function deleteQuarantine(int $id, SecurityScannerService $scannerService): void
    {
        $quarantined = QuarantinedFile::find($id);
        if ($quarantined) {
            $scannerService->deleteFile($quarantined);
            session()->flash('success', "Quarantined threat permanently purged.");
        }
    }

    public function render(SecurityAdvisorService $advisor)
    {
        $servers = Server::select('id', 'name', 'hostname', 'ip_address')->get();
        $domains = Domain::select('id', 'domain', 'hosting_account_id')->get();

        $advisorReport = $this->server
            ? $advisor->auditServer($this->server)
            : ['score' => 0, 'grade' => 'F', 'checks' => []];

        $scans = $this->server
            ? SecurityScan::where('server_id', $this->server->id)->latest()->take(10)->get()
            : collect();

        $quarantinedFiles = QuarantinedFile::where('status', QuarantinedFile::STATUS_QUARANTINED)
            ->latest()
            ->take(20)
            ->get();

        return view('security-center::livewire.security-center-index', [
            'servers' => $servers,
            'domains' => $domains,
            'advisorReport' => $advisorReport,
            'scans' => $scans,
            'quarantinedFiles' => $quarantinedFiles,
        ]);
    }
}
