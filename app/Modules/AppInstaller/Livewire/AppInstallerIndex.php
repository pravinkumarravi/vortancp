<?php

declare(strict_types=1);

namespace App\Modules\AppInstaller\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\AppInstaller\Models\InstalledApp;
use App\Modules\AppInstaller\Services\AppCatalogService;
use App\Modules\AppInstaller\Services\AppInstallerService;
use App\Modules\Domains\Models\Domain;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AppInstallerIndex extends Component
{
    public ?int $selectedAccountId = null;
    public string $activeTab = 'catalog'; // catalog or installed
    public string $selectedCategory = 'All';
    public string $search = '';

    // Install Modal
    public bool $showInstallModal = false;
    public ?string $installing_slug = null;
    public ?array $selected_package = null;
    public ?int $target_domain_id = null;
    public string $sub_path = '';
    public string $admin_email = 'admin@example.com';

    // Uninstall Modal
    public bool $showUninstallModal = false;
    public ?int $uninstalling_app_id = null;
    public bool $remove_files = true;

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
            $this->target_domain_id = $this->account->domains()->first()->id;
        }
    }

    public function getAccountProperty(): ?HostingAccount
    {
        if (! $this->selectedAccountId) {
            return null;
        }

        return HostingAccount::with(['customer', 'domains', 'installedApps.domain'])->find($this->selectedAccountId);
    }

    public function openInstallModal(string $slug, AppCatalogService $catalog): void
    {
        $package = $catalog->getPackage($slug);
        if (! $package) {
            session()->flash('error', 'Package not found.');
            return;
        }

        if (! $this->account) {
            session()->flash('error', 'Please select a hosting account first.');
            return;
        }

        if ($this->account->domains()->count() === 0) {
            session()->flash('error', 'Please configure at least one domain before installing apps.');
            return;
        }

        $this->installing_slug = $slug;
        $this->selected_package = $package;
        $this->target_domain_id = $this->account->domains()->first()->id;
        $this->sub_path = '';
        $this->admin_email = Auth::user()->email ?? 'admin@example.com';
        $this->showInstallModal = true;
    }

    public function installApp(AppInstallerService $installer): void
    {
        $this->validate([
            'target_domain_id' => 'required|exists:domains,id',
            'admin_email' => 'required|email|max:150',
            'sub_path' => 'nullable|string|max:100',
        ]);

        $domain = Domain::where('hosting_account_id', $this->selectedAccountId)->find($this->target_domain_id);
        if (! $domain) {
            session()->flash('error', 'Domain not found under this account.');
            return;
        }

        try {
            $app = $installer->install(
                domain: $domain,
                appSlug: $this->installing_slug,
                adminEmail: $this->admin_email,
                subPath: $this->sub_path ?? ''
            );

            session()->flash('success', "{$app->app_name} installed successfully!");
            $this->showInstallModal = false;
            $this->activeTab = 'installed';
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function confirmUninstall(int $id): void
    {
        $this->uninstalling_app_id = $id;
        $this->showUninstallModal = true;
    }

    public function uninstallApp(AppInstallerService $installer): void
    {
        $app = InstalledApp::where('hosting_account_id', $this->selectedAccountId)->find($this->uninstalling_app_id);
        if ($app) {
            $name = $app->app_name;
            $installer->uninstall($app, $this->remove_files);
            session()->flash('success', "{$name} uninstalled.");
            $this->showUninstallModal = false;
            $this->uninstalling_app_id = null;
        }
    }

    public function render(AppCatalogService $catalog)
    {
        $user = Auth::user();
        $accounts = ($user && $user->hasRole('customer'))
            ? []
            : HostingAccount::select('id', 'username', 'primary_domain')->get();

        $allPackages = $catalog->getAllPackages();

        if ($this->selectedCategory !== 'All') {
            $allPackages = array_filter($allPackages, fn($p) => $p['category'] === $this->selectedCategory);
        }

        if (trim($this->search) !== '') {
            $term = strtolower(trim($this->search));
            $allPackages = array_filter($allPackages, fn($p) => 
                str_contains(strtolower($p['name']), $term) || 
                str_contains(strtolower($p['description']), $term) ||
                str_contains(strtolower($p['category']), $term)
            );
        }

        $installedApps = $this->account
            ? $this->account->installedApps()->with('domain')->latest()->get()
            : collect();

        $domains = $this->account
            ? $this->account->domains()->get()
            : collect();

        return view('app-installer::livewire.app-installer-index', [
            'account' => $this->account,
            'accounts' => $accounts,
            'packages' => $allPackages,
            'categories' => array_merge(['All'], $catalog->getCategories()),
            'installedApps' => $installedApps,
            'domains' => $domains,
        ]);
    }
}
