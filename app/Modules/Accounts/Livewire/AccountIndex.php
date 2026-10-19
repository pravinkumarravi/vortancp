<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Livewire;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('Hosting Accounts - VortanCP')]
class AccountIndex extends Component
{
    use AuthorizesRequests;

    public string $search = '';
    public string $statusFilter = 'all';

    // Create Modal State
    public bool $showCreateModal = false;
    public ?int $selectedCustomerId = null;
    public ?int $selectedPlanId = null;
    public ?int $selectedServerId = null;
    public string $domain = '';
    public string $username = '';

    // Suspend Modal State
    public bool $showSuspendModal = false;
    public ?int $suspendingAccountId = null;
    public string $suspendReason = '';

    protected function rules(): array
    {
        return [
            'selectedCustomerId' => ['required', 'exists:customers,id'],
            'selectedPlanId' => ['required', 'exists:hosting_plans,id'],
            'selectedServerId' => ['required', 'exists:servers,id'],
            'domain' => ['required', 'string', 'max:255', 'unique:hosting_accounts,primary_domain'],
            'username' => ['required', 'string', 'alpha_num', 'between:3,16', 'unique:hosting_accounts,username'],
        ];
    }

    public function updatedDomain(string $value): void
    {
        // Auto-generate clean 8-character system username from domain
        if (empty($this->username)) {
            $cleaned = preg_replace('/[^a-z0-9]/', '', strtolower(explode('.', $value)[0] ?? ''));
            $this->username = substr($cleaned, 0, 8);
        }
    }

    public function createAccount(): void
    {
        $this->authorize('accounts.manage');
        $this->validate();

        $customer = Customer::findOrFail($this->selectedCustomerId);
        $plan = HostingPlan::findOrFail($this->selectedPlanId);
        $server = Server::findOrFail($this->selectedServerId);

        // 1. Create Subscription
        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $server->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // 2. Create Hosting Account (Linux System Sandbox)
        $account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => strtolower($this->username),
            'primary_domain' => strtolower(trim($this->domain)),
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1000 + HostingAccount::count(),
            'gid' => 1000 + HostingAccount::count(),
            'homedir' => "/home/{$this->username}",
            'disk_used_mb' => 0,
            'bandwidth_used_mb' => 0,
        ]);

        $subscription->update(['hosting_account_id' => $account->id]);

        app(AuditLogger::class)->log(
            event: 'account.created',
            auditable: $account,
            newValues: [
                'username' => $account->username,
                'domain' => $account->primary_domain,
                'server' => $server->hostname,
            ]
        );

        $this->reset(['showCreateModal', 'domain', 'username', 'selectedCustomerId', 'selectedPlanId', 'selectedServerId']);
        session()->flash('success', "Hosting account [{$account->username}] created successfully on {$server->name}.");
    }

    public function triggerSuspend(int $accountId): void
    {
        $this->suspendingAccountId = $accountId;
        $this->suspendReason = '';
        $this->showSuspendModal = true;
    }

    public function toggleAccountStatus(): void
    {
        $this->authorize('accounts.manage');

        $account = HostingAccount::findOrFail($this->suspendingAccountId);

        if ($account->status === HostingAccount::STATUS_ACTIVE) {
            $account->update([
                'status' => HostingAccount::STATUS_SUSPENDED,
                'suspended_reason' => $this->suspendReason ?: 'Suspended by administrator',
            ]);
            $msg = "Account [{$account->username}] has been suspended.";
        } else {
            $account->update([
                'status' => HostingAccount::STATUS_ACTIVE,
                'suspended_reason' => null,
            ]);
            $msg = "Account [{$account->username}] has been unsuspended.";
        }

        app(AuditLogger::class)->log(
            event: 'account.status_changed',
            auditable: $account,
            newValues: ['status' => $account->status, 'reason' => $account->suspended_reason]
        );

        $this->showSuspendModal = false;
        session()->flash('success', $msg);
    }

    public function render(): View
    {
        $accounts = HostingAccount::query()
            ->with(['customer', 'server', 'subscription.hostingPlan'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('username', 'like', "%{$this->search}%")
                      ->orWhere('primary_domain', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest('id')
            ->get();

        return view('accounts::livewire.account-index', [
            'accounts' => $accounts,
            'customers' => Customer::where('status', 'active')->get(),
            'plans' => HostingPlan::where('is_active', true)->get(),
            'servers' => Server::where('status', 'active')->get(),
        ]);
    }
}
