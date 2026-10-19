<?php

declare(strict_types=1);

namespace App\Modules\Domains\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\Domains\Services\DomainProvisioningService;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('Domains & Websites - VortanCP')]
class DomainIndex extends Component
{
    use AuthorizesRequests;

    public string $panel = 'admin'; // 'admin' or 'customer'
    public string $search = '';
    public string $typeFilter = 'all';
    public ?int $accountFilter = null;

    // Create Modal State
    public bool $showCreateModal = false;
    public ?int $selectedAccountId = null;
    public string $domainName = '';
    public string $domainType = Domain::TYPE_ADDON;
    public ?int $parentDomainId = null;
    public string $phpVersion = '8.4';
    public string $customDocRoot = '';

    // Delete Modal State
    public bool $showDeleteModal = false;
    public ?int $deletingDomainId = null;

    protected function rules(): array
    {
        return [
            'selectedAccountId' => ['required', 'exists:hosting_accounts,id'],
            'domainName' => ['required', 'string', 'max:255'],
            'domainType' => ['required', 'in:addon,subdomain,alias'],
            'phpVersion' => ['required', 'in:8.4,8.3,8.2,8.1'],
            'parentDomainId' => ['nullable', 'required_if:domainType,subdomain,alias', 'exists:domains,id'],
        ];
    }

    public function mount(string $panel = 'admin'): void
    {
        $this->panel = $panel;
        
        $user = auth()->user();
        if ($this->panel === 'customer' || $user->isCustomer()) {
            $customerAccount = HostingAccount::whereHas('customer', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->first();

            if ($customerAccount) {
                $this->selectedAccountId = $customerAccount->id;
                $this->accountFilter = $customerAccount->id;
            }
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->domainName = '';
        $this->domainType = Domain::TYPE_ADDON;
        $this->parentDomainId = null;
        $this->customDocRoot = '';
        $this->phpVersion = '8.4';

        if (!$this->selectedAccountId) {
            $firstAccount = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE)->first();
            $this->selectedAccountId = $firstAccount?->id;
        }

        $this->showCreateModal = true;
    }

    public function createDomain(DomainProvisioningService $provisioner): void
    {
        $this->authorize('domains.create');
        $this->validate();

        $account = HostingAccount::findOrFail($this->selectedAccountId);
        $parent = $this->parentDomainId ? Domain::find($this->parentDomainId) : null;

        // If subdomain, prefix domainName with parent domain if not full domain
        $fullName = strtolower(trim($this->domainName));
        if ($this->domainType === Domain::TYPE_SUBDOMAIN && $parent && !str_ends_with($fullName, $parent->domain)) {
            $fullName = "{$fullName}.{$parent->domain}";
        }

        try {
            $domain = $provisioner->provision(
                account: $account,
                domainName: $fullName,
                type: $this->domainType,
                phpVersion: $this->phpVersion,
                customDocRoot: $this->customDocRoot ?: null,
                parentDomain: $parent,
            );

            $this->showCreateModal = false;
            session()->flash('success', "Domain [{$domain->domain}] has been configured and virtual host activated.");
        } catch (QuotaExceededException $e) {
            $this->addError('domainName', $e->getMessage());
        } catch (\Exception $e) {
            $this->addError('domainName', $e->getMessage());
        }
    }

    public function toggleHttps(int $domainId): void
    {
        $this->authorize('domains.create');

        $domain = Domain::findOrFail($domainId);
        $domain->update(['force_https' => !$domain->force_https]);

        app(AuditLogger::class)->log(
            event: 'domain.https_toggled',
            auditable: $domain,
            newValues: ['force_https' => $domain->force_https]
        );

        session()->flash('success', "Force HTTPS updated for [{$domain->domain}].");
    }

    public function triggerDelete(int $domainId): void
    {
        $this->deletingDomainId = $domainId;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(DomainProvisioningService $provisioner): void
    {
        $this->authorize('domains.delete');

        $domain = Domain::findOrFail($this->deletingDomainId);

        try {
            $provisioner->delete($domain);
            $this->showDeleteModal = false;
            session()->flash('success', "Domain [{$domain->domain}] removed successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->showDeleteModal = false;
        }
    }

    public function render(): View
    {
        $domains = Domain::query()
            ->with(['hostingAccount.customer', 'virtualHost', 'parentDomain'])
            ->when($this->accountFilter, function ($q) {
                $q->where('hosting_account_id', $this->accountFilter);
            })
            ->when($this->search !== '', function ($q) {
                $q->where('domain', 'like', "%{$this->search}%");
            })
            ->when($this->typeFilter !== 'all', function ($q) {
                $q->where('type', $this->typeFilter);
            })
            ->latest('id')
            ->get();

        $accounts = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE)->get();
        $parentDomains = Domain::where('type', Domain::TYPE_PRIMARY)->get();

        return view('domains::livewire.domain-index', [
            'domains' => $domains,
            'accounts' => $accounts,
            'parentDomains' => $parentDomains,
        ]);
    }
}
