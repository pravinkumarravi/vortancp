<?php

declare(strict_types=1);

namespace App\Modules\Databases\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Databases\Models\Database;
use App\Modules\Databases\Models\DatabaseUser;
use App\Modules\Databases\Models\DatabaseUserAssignment;
use App\Modules\Databases\Services\DatabaseManagerService;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('Databases & Users - VortanCP')]
class DatabaseIndex extends Component
{
    use AuthorizesRequests;

    public string $panel = 'admin';
    public ?int $selectedAccountId = null;
    public string $search = '';

    // Create Database Modal
    public bool $showCreateDbModal = false;
    public string $dbSuffix = '';
    public string $dbEngine = 'mysql';

    // Create User Modal
    public bool $showCreateUserModal = false;
    public string $userSuffix = '';
    public string $userPassword = '';
    public string $userHost = 'localhost';

    // Assign User Modal
    public bool $showAssignModal = false;
    public ?int $assignDbId = null;
    public ?int $assignUserId = null;
    public array $selectedPrivileges = ['ALL'];

    // Change Password Modal
    public bool $showPasswordModal = false;
    public ?int $passwordUserId = null;
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

    public function openCreateDbModal(): void
    {
        $this->resetValidation();
        $this->dbSuffix = '';
        $this->dbEngine = 'mysql';
        $this->showCreateDbModal = true;
    }

    public function createDatabase(DatabaseManagerService $dbService): void
    {
        $this->authorize('databases.manage');
        $this->validate(['dbSuffix' => ['required', 'alpha_dash', 'max:32']]);

        $account = HostingAccount::findOrFail($this->selectedAccountId);

        try {
            $db = $dbService->createDatabase(
                account: $account,
                suffix: $this->dbSuffix,
                engine: $this->dbEngine
            );

            $this->showCreateDbModal = false;
            session()->flash('success', "Database [{$db->name}] created successfully.");
        } catch (QuotaExceededException $e) {
            $this->addError('dbSuffix', $e->getMessage());
        } catch (\Exception $e) {
            $this->addError('dbSuffix', $e->getMessage());
        }
    }

    public function openCreateUserModal(): void
    {
        $this->resetValidation();
        $this->userSuffix = '';
        $this->userPassword = '';
        $this->userHost = 'localhost';
        $this->showCreateUserModal = true;
    }

    public function createDatabaseUser(DatabaseManagerService $dbService): void
    {
        $this->authorize('databases.manage');
        $this->validate([
            'userSuffix' => ['required', 'alpha_dash', 'max:16'],
            'userPassword' => ['required', 'string', 'min:8'],
            'userHost' => ['required', 'string'],
        ]);

        $account = HostingAccount::findOrFail($this->selectedAccountId);

        try {
            $user = $dbService->createDatabaseUser(
                account: $account,
                suffix: $this->userSuffix,
                password: $this->userPassword,
                host: $this->userHost
            );

            $this->showCreateUserModal = false;
            session()->flash('success', "Database user [{$user->username}] created successfully.");
        } catch (\Exception $e) {
            $this->addError('userSuffix', $e->getMessage());
        }
    }

    public function openAssignModal(?int $databaseId = null): void
    {
        $this->resetValidation();
        $this->assignDbId = $databaseId;
        $this->assignUserId = DatabaseUser::where('hosting_account_id', $this->selectedAccountId)->first()?->id;
        $this->selectedPrivileges = ['ALL'];
        $this->showAssignModal = true;
    }

    public function assignUser(DatabaseManagerService $dbService): void
    {
        $this->authorize('databases.manage');
        $this->validate([
            'assignDbId' => ['required', 'exists:databases,id'],
            'assignUserId' => ['required', 'exists:database_users,id'],
        ]);

        $db = Database::findOrFail($this->assignDbId);
        $user = DatabaseUser::findOrFail($this->assignUserId);

        try {
            $dbService->assignUserToDatabase($db, $user, $this->selectedPrivileges);
            $this->showAssignModal = false;
            session()->flash('success', "User [{$user->username}] assigned to database [{$db->name}].");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function deleteDatabase(int $databaseId, DatabaseManagerService $dbService): void
    {
        $this->authorize('databases.manage');
        $db = Database::findOrFail($databaseId);
        $name = $db->name;

        $dbService->deleteDatabase($db);
        session()->flash('success', "Database [{$name}] deleted.");
    }

    public function deleteDatabaseUser(int $userId, DatabaseManagerService $dbService): void
    {
        $this->authorize('databases.manage');
        $user = DatabaseUser::findOrFail($userId);
        $name = $user->username;

        $dbService->deleteDatabaseUser($user);
        session()->flash('success', "Database user [{$name}] deleted.");
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

        $databases = $currentAccount ? Database::where('hosting_account_id', $currentAccount->id)
            ->with(['users', 'server'])
            ->when($this->search !== '', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->get() : collect();

        $dbUsers = $currentAccount ? DatabaseUser::where('hosting_account_id', $currentAccount->id)
            ->with('databases')
            ->get() : collect();

        return view('databases::livewire.database-index', [
            'availableAccounts' => $availableAccounts,
            'currentAccount' => $currentAccount,
            'databases' => $databases,
            'dbUsers' => $dbUsers,
            'allPrivileges' => DatabaseUserAssignment::ALL_PRIVILEGES,
        ]);
    }
}
