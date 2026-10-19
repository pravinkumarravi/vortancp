<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Ssl\Models\SslCertificate;
use App\Modules\Ssl\Services\SslCertificateService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('SSL/TLS Certificates - VortanCP')]
class SslCertificateManager extends Component
{
    use AuthorizesRequests;

    public string $panel = 'admin';
    public string $search = '';
    public string $statusFilter = 'all';

    // Issue Let's Encrypt Modal
    public bool $showLetsEncryptModal = false;
    public ?int $selectedDomainId = null;
    public bool $includeWww = true;

    // Install Custom SSL Modal
    public bool $showCustomModal = false;
    public ?int $customDomainId = null;
    public string $customCertificate = '';
    public string $customPrivateKey = '';
    public string $customChain = '';

    // Inspect Modal
    public bool $showInspectModal = false;
    public ?SslCertificate $inspectingCert = null;

    public function mount(string $panel = 'admin'): void
    {
        $this->panel = $panel;
    }

    public function openLetsEncryptModal(): void
    {
        $this->resetValidation();
        $this->selectedDomainId = Domain::where('status', Domain::STATUS_ACTIVE)->first()?->id;
        $this->includeWww = true;
        $this->showLetsEncryptModal = true;
    }

    public function issueLetsEncrypt(SslCertificateService $sslService): void
    {
        $this->authorize('ssl.manage');
        $this->validate([
            'selectedDomainId' => ['required', 'exists:domains,id'],
        ]);

        $domain = Domain::findOrFail($this->selectedDomainId);

        try {
            $cert = $sslService->issueLetsEncrypt($domain, includeWww: $this->includeWww);
            $this->showLetsEncryptModal = false;
            session()->flash('success', "Let's Encrypt TLS certificate for [{$domain->domain}] issued and activated successfully.");
        } catch (\Exception $e) {
            $this->addError('selectedDomainId', $e->getMessage());
        }
    }

    public function openCustomModal(): void
    {
        $this->resetValidation();
        $this->customDomainId = Domain::where('status', Domain::STATUS_ACTIVE)->first()?->id;
        $this->customCertificate = '';
        $this->customPrivateKey = '';
        $this->customChain = '';
        $this->showCustomModal = true;
    }

    public function installCustom(SslCertificateService $sslService): void
    {
        $this->authorize('ssl.manage');
        $this->validate([
            'customDomainId' => ['required', 'exists:domains,id'],
            'customCertificate' => ['required', 'string'],
            'customPrivateKey' => ['required', 'string'],
        ]);

        $domain = Domain::findOrFail($this->customDomainId);

        try {
            $cert = $sslService->installCustomCertificate(
                domain: $domain,
                certificate: $this->customCertificate,
                privateKey: $this->customPrivateKey,
                chain: $this->customChain ?: null
            );

            $this->showCustomModal = false;
            session()->flash('success', "Custom SSL Certificate for [{$domain->domain}] validated and installed.");
        } catch (\Exception $e) {
            $this->addError('customCertificate', $e->getMessage());
        }
    }

    public function inspectCertificate(int $certId): void
    {
        $this->inspectingCert = SslCertificate::with('domain')->findOrFail($certId);
        $this->showInspectModal = true;
    }

    public function revokeCertificate(int $certId, SslCertificateService $sslService): void
    {
        $this->authorize('ssl.manage');

        $cert = SslCertificate::findOrFail($certId);
        $domainName = $cert->domain->domain;

        $sslService->revokeCertificate($cert);
        session()->flash('success', "SSL Certificate for [{$domainName}] revoked.");
    }

    public function render(): View
    {
        $user = auth()->user();

        $certificates = SslCertificate::query()
            ->with(['domain', 'hostingAccount'])
            ->when($this->panel === 'customer' || $user->isCustomer(), function ($q) use ($user) {
                $q->whereHas('hostingAccount.customer', function ($sub) use ($user) {
                    $sub->where('user_id', $user->id);
                });
            })
            ->when($this->search !== '', function ($q) {
                $q->whereHas('domain', function ($sub) {
                    $sub->where('domain', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->latest('valid_until')
            ->get();

        $domains = Domain::where('status', Domain::STATUS_ACTIVE)->get();

        return view('ssl::livewire.ssl-certificate-manager', [
            'certificates' => $certificates,
            'domains' => $domains,
        ]);
    }
}
