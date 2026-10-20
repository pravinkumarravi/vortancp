<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Livewire;

use App\Modules\Domains\Models\Domain;
use App\Modules\Ssl\Models\SslCertificate;
use App\Modules\Ssl\Services\AutoSslFleetService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('AutoSSL Fleet Manager - VortanCP')]
class AutoSslManager extends Component
{
    use AuthorizesRequests;

    public string $search = '';
    public string $statusFilter = 'all';

    /**
     * @var array{total_scanned: int, issued: int, renewed: int, failed: int, results: array<int, array<string, mixed>>}|null
     */
    public ?array $lastRunResult = null;

    public function runFleetAutoSsl(AutoSslFleetService $autoSsl): void
    {
        $this->authorize('ssl.manage');

        $this->lastRunResult = $autoSsl->runAutoSsl();

        $issued = $this->lastRunResult['issued'];
        $renewed = $this->lastRunResult['renewed'];
        $failed = $this->lastRunResult['failed'];

        session()->flash('success', "AutoSSL completed: {$issued} issued, {$renewed} renewed, {$failed} failed.");
    }

    public function runDomainAutoSsl(int $domainId, AutoSslFleetService $autoSsl): void
    {
        $this->authorize('ssl.manage');

        $result = $autoSsl->runAutoSsl(domainId: $domainId);
        $domainResult = $result['results'][0] ?? null;

        if ($domainResult && $domainResult['action'] !== 'failed') {
            session()->flash('success', "AutoSSL for {$domainResult['domain']}: {$domainResult['message']}");
        } else {
            session()->flash('error', "AutoSSL failed: " . ($domainResult['message'] ?? 'Unknown error'));
        }
    }

    public function toggleAutoRenew(int $certId): void
    {
        $this->authorize('ssl.manage');

        $cert = SslCertificate::findOrFail($certId);
        $cert->update(['auto_renew' => !$cert->auto_renew]);

        session()->flash('success', "Auto-renew updated for [{$cert->subject_dn}].");
    }

    public function render(AutoSslFleetService $autoSsl): View
    {
        $overview = $autoSsl->getFleetOverview();

        $now = now();
        $thirtyDays = $now->copy()->addDays(30);

        $domains = Domain::query()
            ->where('status', Domain::STATUS_ACTIVE)
            ->with(['hostingAccount', 'activeSslCertificate'])
            ->when($this->search !== '', fn ($q) => $q->where('domain', 'like', "%{$this->search}%"))
            ->get();

        // Apply status filter
        if ($this->statusFilter === 'secured') {
            $domains = $domains->filter(fn ($d) => $d->activeSslCertificate && $d->activeSslCertificate->valid_until > $thirtyDays);
        } elseif ($this->statusFilter === 'expiring') {
            $domains = $domains->filter(fn ($d) => $d->activeSslCertificate && $d->activeSslCertificate->valid_until <= $thirtyDays && $d->activeSslCertificate->valid_until > $now);
        } elseif ($this->statusFilter === 'unsecured') {
            $domains = $domains->filter(fn ($d) => !$d->activeSslCertificate || $d->activeSslCertificate->valid_until <= $now);
        }

        return view('ssl::livewire.autossl-manager', [
            'overview' => $overview,
            'domains' => $domains,
        ]);
    }
}
