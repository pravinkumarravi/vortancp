<?php

declare(strict_types=1);

namespace App\Modules\WordPressToolkit\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\WordPressToolkit\Models\WordPressInstance;
use App\Modules\WordPressToolkit\Services\WordPressManagerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WordPressIndex extends Component
{
    public ?int $selectedAccountId = null;

    // Install Modal
    public bool $showInstallModal = false;
    public ?int $new_domain_id = null;
    public string $new_site_title = 'My WordPress Site';
    public string $new_admin_email = 'admin@example.com';
    public string $new_sub_path = '';
    public bool $new_auto_update_core = true;
    public string $new_wp_version = '6.7.1';

    // Delete Confirmation
    public bool $showDeleteModal = false;
    public ?int $deleting_instance_id = null;

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

        if ($this->account && $this->account->domains()->exists()) {
            $this->new_domain_id = $this->account->domains()->first()->id;
        }
    }

    public function getAccountProperty(): ?HostingAccount
    {
        if (! $this->selectedAccountId) {
            return null;
        }

        return HostingAccount::with(['customer', 'domains', 'wordPressInstances.domain'])->find($this->selectedAccountId);
    }

    public function updatedSelectedAccountId(): void
    {
        if ($this->account && $this->account->domains()->exists()) {
            $this->new_domain_id = $this->account->domains()->first()->id;
        } else {
            $this->new_domain_id = null;
        }
    }

    public function openInstallModal(): void
    {
        if (! $this->account) {
            session()->flash('error', 'Please select a hosting account first.');
            return;
        }

        if ($this->account->domains()->count() === 0) {
            session()->flash('error', 'This account has no domains. Please add a domain first.');
            return;
        }

        $this->new_domain_id = $this->account->domains()->first()->id;
        $this->showInstallModal = true;
    }

    public function installWordPress(WordPressManagerService $manager): void
    {
        $this->validate([
            'new_domain_id' => 'required|exists:domains,id',
            'new_site_title' => 'required|string|max:100',
            'new_admin_email' => 'required|email|max:150',
            'new_sub_path' => 'nullable|string|max:100',
            'new_wp_version' => 'required|string',
        ]);

        $domain = Domain::where('hosting_account_id', $this->selectedAccountId)->find($this->new_domain_id);
        if (! $domain) {
            session()->flash('error', 'Domain not found under this account.');
            return;
        }

        try {
            $instance = $manager->install(
                domain: $domain,
                siteTitle: $this->new_site_title,
                adminEmail: $this->new_admin_email,
                subPath: $this->new_sub_path ?? '',
                autoUpdateCore: $this->new_auto_update_core,
                wpVersion: $this->new_wp_version
            );

            session()->flash('success', "WordPress instance '{$instance->site_title}' provisioned successfully!");
            $this->showInstallModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleMaintenance(int $id, WordPressManagerService $manager): void
    {
        $instance = WordPressInstance::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($instance) {
            $manager->toggleMaintenance($instance);
            session()->flash('success', "Maintenance mode status toggled for '{$instance->site_title}'.");
        }
    }

    public function toggleHardening(int $id, string $feature, WordPressManagerService $manager): void
    {
        $instance = WordPressInstance::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($instance) {
            $manager->toggleHardening($instance, $feature);
            session()->flash('success', "Security setting '{$feature}' updated.");
        }
    }

    public function ssoLogin(int $id, WordPressManagerService $manager): void
    {
        $instance = WordPressInstance::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($instance) {
            $url = $manager->generateSsoUrl($instance);
            $this->redirect($url);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deleting_instance_id = $id;
        $this->showDeleteModal = true;
    }

    public function deleteInstance(WordPressManagerService $manager): void
    {
        $instance = WordPressInstance::where('hosting_account_id', $this->selectedAccountId)->find($this->deleting_instance_id);
        if ($instance) {
            $manager->deleteInstance($instance);
            session()->flash('success', "WordPress instance removed.");
            $this->showDeleteModal = false;
            $this->deleting_instance_id = null;
        }
    }

    public function render()
    {
        $user = Auth::user();
        $accounts = ($user && $user->hasRole('customer'))
            ? []
            : HostingAccount::select('id', 'username', 'primary_domain')->get();

        $instances = $this->account
            ? $this->account->wordPressInstances()->with('domain')->latest()->get()
            : collect();

        $domains = $this->account
            ? $this->account->domains()->get()
            : collect();

        return view('wordpress-toolkit::livewire.wordpress-index', [
            'account' => $this->account,
            'accounts' => $accounts,
            'instances' => $instances,
            'domains' => $domains,
        ]);
    }
}
