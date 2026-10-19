<?php

declare(strict_types=1);

namespace App\Modules\Git\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Git\Models\GitRepository;
use App\Modules\Git\Services\GitRepositoryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GitIndex extends Component
{
    public ?int $selectedAccountId = null;

    // Create Modal
    public bool $showCreateModal = false;
    public string $name = '';
    public ?int $domain_id = null;
    public ?string $remote_url = null;
    public string $branch = 'main';
    public ?string $deploy_path = null;
    public bool $auto_deploy = true;
    public string $deploy_script = "#!/bin/bash\necho 'Deploying repository...'\n";

    // View Deploy Key Modal
    public bool $showKeyModal = false;
    public ?int $viewing_repo_id = null;
    public ?string $viewing_public_key = null;

    // View Webhook Modal
    public bool $showWebhookModal = false;
    public ?string $viewing_webhook_url = null;
    public ?string $viewing_webhook_secret = null;

    // Deploy Output Modal
    public bool $showDeployOutputModal = false;
    public string $deploy_log = '';

    // Delete Modal
    public bool $showDeleteModal = false;
    public ?int $deleting_repo_id = null;

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
            $this->domain_id = $this->account->domains()->first()->id;
        }
    }

    public function getAccountProperty(): ?HostingAccount
    {
        if (! $this->selectedAccountId) {
            return null;
        }

        return HostingAccount::with(['customer', 'domains', 'gitRepositories.domain'])->find($this->selectedAccountId);
    }

    public function openCreateModal(): void
    {
        if (! $this->account) {
            session()->flash('error', 'Please select a hosting account first.');
            return;
        }

        $this->name = '';
        $this->remote_url = null;
        $this->branch = 'main';
        $this->deploy_path = null;
        $this->auto_deploy = true;
        $this->domain_id = $this->account->domains()->first()?->id;
        $this->showCreateModal = true;
    }

    public function createRepository(GitRepositoryService $gitService): void
    {
        $this->validate([
            'name' => 'required|string|regex:/^[a-zA-Z0-9_\-\.]+$/|max:50',
            'remote_url' => 'nullable|string|max:255',
            'branch' => 'required|string|max:50',
            'domain_id' => 'nullable|exists:domains,id',
            'deploy_path' => 'nullable|string|max:255',
        ]);

        $account = $this->account;
        if (! $account) {
            session()->flash('error', 'No hosting account selected.');
            return;
        }

        $domain = $this->domain_id ? Domain::find($this->domain_id) : null;

        try {
            $repo = $gitService->createRepository(
                account: $account,
                name: $this->name,
                domain: $domain,
                remoteUrl: $this->remote_url,
                branch: $this->branch,
                deployPath: $this->deploy_path,
                deployScript: $this->deploy_script,
                autoDeploy: $this->auto_deploy
            );

            session()->flash('success', "Git repository '{$repo->name}' registered successfully.");
            $this->showCreateModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function deployNow(int $repoId, GitRepositoryService $gitService): void
    {
        $repo = GitRepository::where('hosting_account_id', $this->selectedAccountId)->find($repoId);
        if (! $repo) {
            session()->flash('error', 'Repository not found.');
            return;
        }

        try {
            $result = $gitService->deploy($repo);
            $this->deploy_log = $result['log'];
            $this->showDeployOutputModal = true;
            session()->flash('success', "Deployment for '{$repo->name}' executed successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function viewDeployKey(int $repoId): void
    {
        $repo = GitRepository::where('hosting_account_id', $this->selectedAccountId)->find($repoId);
        if ($repo) {
            $this->viewing_repo_id = $repo->id;
            $this->viewing_public_key = $repo->deploy_key_public;
            $this->showKeyModal = true;
        }
    }

    public function viewWebhook(int $repoId): void
    {
        $repo = GitRepository::where('hosting_account_id', $this->selectedAccountId)->find($repoId);
        if ($repo) {
            $this->viewing_webhook_url = url("/api/git/webhook/{$repo->id}");
            $this->viewing_webhook_secret = $repo->webhook_secret;
            $this->showWebhookModal = true;
        }
    }

    public function confirmDelete(int $repoId): void
    {
        $this->deleting_repo_id = $repoId;
        $this->showDeleteModal = true;
    }

    public function deleteRepository(GitRepositoryService $gitService): void
    {
        $repo = GitRepository::where('hosting_account_id', $this->selectedAccountId)->find($this->deleting_repo_id);
        if ($repo) {
            $gitService->deleteRepository($repo);
            session()->flash('success', "Repository deleted successfully.");
            $this->showDeleteModal = false;
            $this->deleting_repo_id = null;
        }
    }

    public function render()
    {
        $user = Auth::user();
        $accounts = ($user && $user->hasRole('customer'))
            ? []
            : HostingAccount::select('id', 'username', 'primary_domain')->get();

        $repositories = $this->account
            ? $this->account->gitRepositories()->with('domain')->latest()->get()
            : collect();

        $domains = $this->account
            ? $this->account->domains()->get()
            : collect();

        return view('git::livewire.git-index', [
            'account' => $this->account,
            'accounts' => $accounts,
            'repositories' => $repositories,
            'domains' => $domains,
        ]);
    }
}
