<?php

declare(strict_types=1);

namespace App\Modules\Email\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Email\Models\MailAlias;
use App\Modules\Email\Models\MailDomain;
use App\Modules\Email\Models\Mailbox;
use App\Modules\Email\Services\MailboxManagerService;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('Email Accounts - VortanCP')]
class MailboxManager extends Component
{
    use AuthorizesRequests;

    public string $panel = 'admin';
    public ?int $selectedAccountId = null;
    public string $search = '';
    public string $activeTab = 'mailboxes'; // 'mailboxes' or 'forwarders'

    // Create Mailbox Modal
    public bool $showCreateMailboxModal = false;
    public ?int $selectedDomainId = null;
    public string $mailboxUser = '';
    public string $mailboxPassword = '';
    public int $mailboxQuotaMb = 1024;

    // Create Forwarder Modal
    public bool $showCreateForwarderModal = false;
    public string $forwarderSource = '';
    public string $forwarderDestination = '';

    // Change Password Modal
    public bool $showPasswordModal = false;
    public ?int $passwordMailboxId = null;
    public string $newPassword = '';

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

    public function openCreateMailboxModal(): void
    {
        $this->resetValidation();
        $this->selectedDomainId = Domain::where('hosting_account_id', $this->selectedAccountId)->first()?->id;
        $this->mailboxUser = '';
        $this->mailboxPassword = '';
        $this->mailboxQuotaMb = 1024;
        $this->showCreateMailboxModal = true;
    }

    public function createMailbox(MailboxManagerService $mailService): void
    {
        $this->authorize('email.manage');
        $this->validate([
            'selectedDomainId' => ['required', 'exists:domains,id'],
            'mailboxUser' => ['required', 'string', 'alpha_dash', 'max:64'],
            'mailboxPassword' => ['required', 'string', 'min:8'],
            'mailboxQuotaMb' => ['required', 'integer', 'min:100'],
        ]);

        $domain = Domain::findOrFail($this->selectedDomainId);

        try {
            $mailDomain = $mailService->ensureMailDomain($domain);
            $mailbox = $mailService->createMailbox(
                mailDomain: $mailDomain,
                localPart: $this->mailboxUser,
                password: $this->mailboxPassword,
                quotaMb: $this->mailboxQuotaMb
            );

            $this->showCreateMailboxModal = false;
            session()->flash('success', "Mailbox [{$mailbox->email}] created successfully.");
        } catch (QuotaExceededException $e) {
            $this->addError('mailboxUser', $e->getMessage());
        } catch (\Exception $e) {
            $this->addError('mailboxUser', $e->getMessage());
        }
    }

    public function openCreateForwarderModal(): void
    {
        $this->resetValidation();
        $this->selectedDomainId = Domain::where('hosting_account_id', $this->selectedAccountId)->first()?->id;
        $this->forwarderSource = '';
        $this->forwarderDestination = '';
        $this->showCreateForwarderModal = true;
    }

    public function createForwarder(MailboxManagerService $mailService): void
    {
        $this->authorize('email.manage');
        $this->validate([
            'selectedDomainId' => ['required', 'exists:domains,id'],
            'forwarderSource' => ['required', 'string', 'alpha_dash'],
            'forwarderDestination' => ['required', 'email'],
        ]);

        $domain = Domain::findOrFail($this->selectedDomainId);

        try {
            $mailDomain = $mailService->ensureMailDomain($domain);
            $fullSource = "{$this->forwarderSource}@{$domain->domain}";
            $mailService->createAlias($mailDomain, $fullSource, $this->forwarderDestination);

            $this->showCreateForwarderModal = false;
            session()->flash('success', "Email forwarder for [{$fullSource}] created.");
        } catch (\Exception $e) {
            $this->addError('forwarderDestination', $e->getMessage());
        }
    }

    public function deleteMailbox(int $mailboxId, MailboxManagerService $mailService): void
    {
        $this->authorize('email.manage');
        $mailbox = Mailbox::findOrFail($mailboxId);
        $email = $mailbox->email;

        $mailService->deleteMailbox($mailbox);
        session()->flash('success', "Mailbox [{$email}] deleted.");
    }

    public function deleteForwarder(int $aliasId, MailboxManagerService $mailService): void
    {
        $this->authorize('email.manage');
        $alias = MailAlias::findOrFail($aliasId);
        $source = $alias->source_email;

        $mailService->deleteAlias($alias);
        session()->flash('success', "Forwarder [{$source}] deleted.");
    }

    public function render(): View
    {
        $user = auth()->user();

        $accountsQuery = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE);
        if ($this->panel === 'customer' || $user->isCustomer()) {
            $accountsQuery->whereHas('customer', fn($q) => $q->where('user_id', $user->id));
        }
        $availableAccounts = $accountsQuery->get();

        $currentAccount = $this->selectedAccountId ? HostingAccount::find($this->selectedAccountId) : null;

        $mailboxes = $currentAccount ? Mailbox::where('hosting_account_id', $currentAccount->id)
            ->with('mailDomain')
            ->when($this->search !== '', fn($q) => $q->where('email', 'like', "%{$this->search}%"))
            ->get() : collect();

        $aliases = $currentAccount ? MailAlias::whereHas('mailDomain', fn($q) => $q->where('hosting_account_id', $currentAccount->id))
            ->with('mailDomain')
            ->get() : collect();

        $domains = $currentAccount ? Domain::where('hosting_account_id', $currentAccount->id)->get() : collect();

        return view('email::livewire.mailbox-manager', [
            'availableAccounts' => $availableAccounts,
            'currentAccount' => $currentAccount,
            'mailboxes' => $mailboxes,
            'aliases' => $aliases,
            'domains' => $domains,
        ]);
    }
}
