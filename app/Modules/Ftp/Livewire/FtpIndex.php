<?php

declare(strict_types=1);

namespace App\Modules\Ftp\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Ftp\Models\FtpAccount;
use App\Modules\Ftp\Services\FtpManagerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FtpIndex extends Component
{
    public ?int $selectedAccountId = null;

    // Create Account form
    public string $login_user = '';
    public string $password = '';
    public string $directory = 'public_html';
    public ?int $quota_mb = null;
    public ?int $upload_bandwidth_kb = null;
    public ?int $download_bandwidth_kb = null;

    // Modals
    public bool $showCreateModal = false;
    public bool $showPasswordModal = false;
    public bool $showConnectionModal = false;

    public ?int $editing_ftp_id = null;
    public string $new_password = '';
    public ?int $viewing_ftp_id = null;

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
    }

    public function getAccountProperty(): ?HostingAccount
    {
        if (! $this->selectedAccountId) {
            return null;
        }

        return HostingAccount::with(['customer', 'server', 'ftpAccounts'])->find($this->selectedAccountId);
    }

    public function createAccount(FtpManagerService $ftpService): void
    {
        $this->validate([
            'login_user' => 'required|string|alpha_dash|min:2|max:32',
            'password' => 'required|string|min:8',
            'directory' => 'required|string|max:255',
            'quota_mb' => 'nullable|integer|min:0',
            'upload_bandwidth_kb' => 'nullable|integer|min:0',
            'download_bandwidth_kb' => 'nullable|integer|min:0',
        ]);

        $account = $this->account;
        if (! $account) {
            session()->flash('error', 'No active hosting account selected.');
            return;
        }

        try {
            $ftpService->createAccount(
                account: $account,
                loginPart: $this->login_user,
                password: $this->password,
                relativePath: $this->directory,
                quotaMb: $this->quota_mb,
                uploadKb: $this->upload_bandwidth_kb,
                downloadKb: $this->download_bandwidth_kb
            );

            session()->flash('success', "FTP user successfully created.");
            $this->reset(['login_user', 'password', 'directory', 'quota_mb', 'upload_bandwidth_kb', 'download_bandwidth_kb', 'showCreateModal']);
            $this->directory = 'public_html';
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openPasswordModal(int $id): void
    {
        $this->editing_ftp_id = $id;
        $this->new_password = '';
        $this->showPasswordModal = true;
    }

    public function updatePassword(FtpManagerService $ftpService): void
    {
        $this->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $ftpAccount = FtpAccount::where('hosting_account_id', $this->selectedAccountId)->find($this->editing_ftp_id);
        if (! $ftpAccount) {
            session()->flash('error', 'FTP account not found.');
            return;
        }

        try {
            $ftpService->updatePassword($ftpAccount, $this->new_password);
            session()->flash('success', "Password updated successfully for {$ftpAccount->username}.");
            $this->showPasswordModal = false;
            $this->new_password = '';
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleStatus(int $id, FtpManagerService $ftpService): void
    {
        $ftpAccount = FtpAccount::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($ftpAccount) {
            $ftpService->toggleStatus($ftpAccount);
            session()->flash('success', "Status toggled for {$ftpAccount->username}.");
        }
    }

    public function deleteAccount(int $id, FtpManagerService $ftpService): void
    {
        $ftpAccount = FtpAccount::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($ftpAccount) {
            $ftpService->deleteAccount($ftpAccount);
            session()->flash('success', "FTP account deleted.");
        }
    }

    public function openConnectionDetails(int $id): void
    {
        $this->viewing_ftp_id = $id;
        $this->showConnectionModal = true;
    }

    public function render()
    {
        $accounts = Auth::user()->hasRole('customer')
            ? []
            : HostingAccount::select('id', 'username', 'primary_domain')->get();

        $ftpAccounts = $this->account
            ? $this->account->ftpAccounts()->latest()->get()
            : collect();

        $viewingAccount = $this->viewing_ftp_id
            ? FtpAccount::find($this->viewing_ftp_id)
            : null;

        return view('ftp::livewire.ftp-index', [
            'account' => $this->account,
            'accounts' => $accounts,
            'ftpAccounts' => $ftpAccounts,
            'viewingAccount' => $viewingAccount,
        ]);
    }
}
