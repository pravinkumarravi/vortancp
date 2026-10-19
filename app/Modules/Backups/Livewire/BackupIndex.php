<?php

declare(strict_types=1);

namespace App\Modules\Backups\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Backups\Models\Backup;
use App\Modules\Backups\Models\BackupSchedule;
use App\Modules\Backups\Services\BackupArchiveService;
use App\Modules\Backups\Services\BackupManagerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BackupIndex extends Component
{
    public ?int $selectedAccountId = null;
    public string $activeTab = 'backups'; // backups or schedules

    // Create Backup Form
    public string $backup_type = Backup::TYPE_FULL;
    public string $destination = 'local';
    public bool $showCreateBackupModal = false;

    // Create Schedule Form
    public string $sched_frequency = BackupSchedule::FREQ_DAILY;
    public int $sched_retention = 7;
    public bool $sched_include_files = true;
    public bool $sched_include_databases = true;
    public bool $sched_include_emails = true;
    public bool $showCreateScheduleModal = false;

    // Restore Modal
    public ?int $selected_backup_id = null;
    public bool $restore_files = true;
    public bool $restore_databases = true;
    public bool $restore_emails = true;
    public bool $showRestoreModal = false;
    public ?string $restore_result = null;

    // Manifest View Modal
    public ?int $viewing_backup_id = null;
    public array $viewing_manifest = [];
    public bool $showManifestModal = false;

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

        return HostingAccount::with(['customer', 'server', 'backups', 'backupSchedules'])->find($this->selectedAccountId);
    }

    public function generateBackup(BackupArchiveService $archiveService): void
    {
        $account = $this->account;
        if (! $account) {
            session()->flash('error', 'No hosting account selected.');
            return;
        }

        try {
            $backup = $archiveService->generateBackup(
                account: $account,
                type: $this->backup_type,
                destination: $this->destination
            );

            session()->flash('success', "Backup '{$backup->filename}' generated successfully.");
            $this->showCreateBackupModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openRestoreModal(int $backupId): void
    {
        $this->selected_backup_id = $backupId;
        $this->restore_result = null;
        $this->showRestoreModal = true;
    }

    public function performRestore(BackupArchiveService $archiveService): void
    {
        $backup = Backup::where('hosting_account_id', $this->selectedAccountId)->find($this->selected_backup_id);
        if (! $backup) {
            session()->flash('error', 'Backup not found.');
            return;
        }

        $components = [];
        if ($this->restore_files) $components[] = 'files';
        if ($this->restore_databases) $components[] = 'databases';
        if ($this->restore_emails) $components[] = 'emails';

        if (empty($components)) {
            session()->flash('error', 'Select at least one component to restore.');
            return;
        }

        try {
            $result = $archiveService->restoreBackup($backup, $components);
            session()->flash('success', "Restore completed successfully for " . implode(', ', $components) . ".");
            $this->showRestoreModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function createSchedule(BackupManagerService $backupService): void
    {
        $account = $this->account;
        if (! $account) {
            session()->flash('error', 'No hosting account selected.');
            return;
        }

        try {
            $backupService->createSchedule(
                account: $account,
                frequency: $this->sched_frequency,
                retentionCount: $this->sched_retention,
                destination: 'local',
                includeFiles: $this->sched_include_files,
                includeDatabases: $this->sched_include_databases,
                includeEmails: $this->sched_include_emails
            );

            session()->flash('success', "Automated backup policy created.");
            $this->showCreateScheduleModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleSchedule(int $id, BackupManagerService $backupService): void
    {
        $schedule = BackupSchedule::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($schedule) {
            $backupService->toggleSchedule($schedule);
            session()->flash('success', "Backup schedule status updated.");
        }
    }

    public function deleteSchedule(int $id, BackupManagerService $backupService): void
    {
        $schedule = BackupSchedule::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($schedule) {
            $backupService->deleteSchedule($schedule);
            session()->flash('success', "Backup schedule removed.");
        }
    }

    public function deleteBackup(int $id, BackupArchiveService $archiveService): void
    {
        $backup = Backup::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($backup) {
            $archiveService->deleteBackup($backup);
            session()->flash('success', "Backup archive deleted from disk.");
        }
    }

    public function viewManifest(int $id): void
    {
        $backup = Backup::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($backup) {
            $this->viewing_backup_id = $backup->id;
            $this->viewing_manifest = $backup->metadata ?? [];
            $this->showManifestModal = true;
        }
    }

    public function render()
    {
        $accounts = Auth::user()->hasRole('customer')
            ? []
            : HostingAccount::select('id', 'username', 'primary_domain')->get();

        $backups = $this->account
            ? $this->account->backups()->latest()->get()
            : collect();

        $schedules = $this->account
            ? $this->account->backupSchedules()->latest()->get()
            : collect();

        return view('backups::livewire.backup-index', [
            'account' => $this->account,
            'accounts' => $accounts,
            'backups' => $backups,
            'schedules' => $schedules,
        ]);
    }
}
