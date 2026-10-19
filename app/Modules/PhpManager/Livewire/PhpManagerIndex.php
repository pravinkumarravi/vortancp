<?php

declare(strict_types=1);

namespace App\Modules\PhpManager\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\PhpManager\Models\PhpPool;
use App\Modules\PhpManager\Services\PhpManagerService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('PHP Multi-Version Manager - VortanCP')]
class PhpManagerIndex extends Component
{
    use AuthorizesRequests;

    public string $panel = 'admin';
    public ?int $selectedAccountId = null;

    // Change Version Modal
    public bool $showVersionModal = false;
    public ?int $selectedDomainId = null;
    public string $targetVersion = '8.4';

    // Directives Modal
    public bool $showDirectivesModal = false;
    public ?int $editingPoolId = null;
    public string $memoryLimit = '256M';
    public int $maxExecutionTime = 60;
    public string $uploadMaxFilesize = '64M';
    public string $postMaxSize = '64M';
    public bool $displayErrors = false;

    // View Raw Pool Config
    public bool $showRawModal = false;
    public string $rawPoolContent = '';

    public function mount(string $panel = 'admin', ?int $accountId = null): void
    {
        $this->panel = $panel;
        $user = auth()->user();

        if ($accountId) {
            $this->selectedAccountId = $accountId;
        } else {
            $query = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE);
            if ($this->panel === 'customer' || $user->isCustomer()) {
                $query->whereHas('customer', fn($q) => $q->where('user_id', $user->id));
            }
            $this->selectedAccountId = $query->first()?->id;
        }
    }

    public function openVersionModal(int $domainId): void
    {
        $domain = Domain::findOrFail($domainId);
        $this->selectedDomainId = $domain->id;
        $this->targetVersion = $domain->php_version;
        $this->showVersionModal = true;
    }

    public function saveVersion(PhpManagerService $phpService): void
    {
        $this->authorize('domains.create');

        $domain = Domain::findOrFail($this->selectedDomainId);
        $phpService->changeDomainPhpVersion($domain, $this->targetVersion);

        // Ensure pool exists for target version
        $phpService->ensurePool($domain->hostingAccount, $this->targetVersion);

        $this->showVersionModal = false;
        session()->flash('success', "PHP version for [{$domain->domain}] updated to {$this->targetVersion}.");
    }

    public function openDirectivesModal(int $poolId): void
    {
        $pool = PhpPool::findOrFail($poolId);
        $this->editingPoolId = $pool->id;
        $this->memoryLimit = $pool->memory_limit;
        $this->maxExecutionTime = $pool->max_execution_time;
        $this->uploadMaxFilesize = $pool->upload_max_filesize;
        $this->postMaxSize = $pool->post_max_size;
        $this->displayErrors = $pool->display_errors;
        $this->showDirectivesModal = true;
    }

    public function saveDirectives(PhpManagerService $phpService): void
    {
        $this->authorize('accounts.manage');

        $pool = PhpPool::findOrFail($this->editingPoolId);
        $phpService->updatePoolDirectives($pool, [
            'memory_limit' => $this->memoryLimit,
            'max_execution_time' => $this->maxExecutionTime,
            'upload_max_filesize' => $this->uploadMaxFilesize,
            'post_max_size' => $this->postMaxSize,
            'display_errors' => $this->displayErrors,
        ]);

        $this->showDirectivesModal = false;
        session()->flash('success', "PHP directives for pool [{$pool->hostingAccount->username}] updated.");
    }

    public function viewRawConfig(int $poolId, PhpManagerService $phpService): void
    {
        $pool = PhpPool::findOrFail($poolId);
        $this->rawPoolContent = $phpService->compilePool($pool);
        $this->showRawModal = true;
    }

    public function render(PhpManagerService $phpService): View
    {
        $user = auth()->user();

        $accountsQuery = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE);
        if ($this->panel === 'customer' || $user->isCustomer()) {
            $accountsQuery->whereHas('customer', fn($q) => $q->where('user_id', $user->id));
        }
        $availableAccounts = $accountsQuery->get();

        $currentAccount = $this->selectedAccountId ? HostingAccount::find($this->selectedAccountId) : null;

        // Auto-ensure default pool for current account
        if ($currentAccount) {
            $phpService->ensurePool($currentAccount, '8.4');
        }

        $pools = $currentAccount ? PhpPool::where('hosting_account_id', $currentAccount->id)->get() : collect();
        $domains = $currentAccount ? Domain::where('hosting_account_id', $currentAccount->id)->get() : collect();

        $runtimes = [
            ['version' => '8.4', 'status' => 'Active', 'is_default' => true, 'path' => '/usr/bin/php8.4', 'fpm' => 'php8.4-fpm.service'],
            ['version' => '8.3', 'status' => 'Active', 'is_default' => false, 'path' => '/usr/bin/php8.3', 'fpm' => 'php8.3-fpm.service'],
            ['version' => '8.2', 'status' => 'Active', 'is_default' => false, 'path' => '/usr/bin/php8.2', 'fpm' => 'php8.2-fpm.service'],
            ['version' => '8.1', 'status' => 'Active', 'is_default' => false, 'path' => '/usr/bin/php8.1', 'fpm' => 'php8.1-fpm.service'],
        ];

        return view('phpmanager::livewire.php-manager-index', [
            'availableAccounts' => $availableAccounts,
            'currentAccount' => $currentAccount,
            'pools' => $pools,
            'domains' => $domains,
            'runtimes' => $runtimes,
        ]);
    }
}
