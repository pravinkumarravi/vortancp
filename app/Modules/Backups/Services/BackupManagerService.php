<?php

declare(strict_types=1);

namespace App\Modules\Backups\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Backups\Models\Backup;
use App\Modules\Backups\Models\BackupSchedule;
use InvalidArgumentException;

class BackupManagerService
{
    public function __construct(
        protected BackupArchiveService $archiveService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create an automated backup schedule.
     */
    public function createSchedule(
        HostingAccount $account,
        string $frequency = BackupSchedule::FREQ_DAILY,
        int $retentionCount = 7,
        string $destination = 'local',
        bool $includeFiles = true,
        bool $includeDatabases = true,
        bool $includeEmails = true
    ): BackupSchedule {
        $validFreq = [BackupSchedule::FREQ_DAILY, BackupSchedule::FREQ_WEEKLY, BackupSchedule::FREQ_MONTHLY];
        if (! in_array($frequency, $validFreq, true)) {
            throw new InvalidArgumentException("Invalid schedule frequency '{$frequency}'.");
        }

        if ($retentionCount < 1 || $retentionCount > 100) {
            throw new InvalidArgumentException("Retention count must be between 1 and 100.");
        }

        $schedule = BackupSchedule::create([
            'hosting_account_id' => $account->id,
            'frequency' => $frequency,
            'retention_count' => $retentionCount,
            'destination' => $destination,
            'include_files' => $includeFiles,
            'include_databases' => $includeDatabases,
            'include_emails' => $includeEmails,
            'is_active' => true,
        ]);

        $this->auditLogger->log(
            event: 'backup_schedule.created',
            auditable: $schedule,
            oldValues: null,
            newValues: [
                'frequency' => $frequency,
                'retention_count' => $retentionCount,
            ]
        );

        return $schedule;
    }

    /**
     * Toggle active state of a schedule.
     */
    public function toggleSchedule(BackupSchedule $schedule): void
    {
        $old = $schedule->is_active;
        $schedule->update([
            'is_active' => ! $schedule->is_active,
        ]);

        $this->auditLogger->log(
            event: 'backup_schedule.toggled',
            auditable: $schedule,
            oldValues: ['is_active' => $old],
            newValues: ['is_active' => $schedule->is_active]
        );
    }

    /**
     * Delete a schedule.
     */
    public function deleteSchedule(BackupSchedule $schedule): void
    {
        $this->auditLogger->log(
            event: 'backup_schedule.deleted',
            auditable: $schedule,
            oldValues: ['frequency' => $schedule->frequency],
            newValues: null
        );

        $schedule->delete();
    }

    /**
     * Prune older backups according to retention limit.
     */
    public function pruneExpiredBackups(HostingAccount $account, int $keepCount = 7): int
    {
        $backups = $account->backups()
            ->where('status', Backup::STATUS_COMPLETED)
            ->latest()
            ->get();

        if ($backups->count() <= $keepCount) {
            return 0;
        }

        $toDelete = $backups->slice($keepCount);
        $deletedCount = 0;

        foreach ($toDelete as $backup) {
            $this->archiveService->deleteBackup($backup);
            $deletedCount++;
        }

        return $deletedCount;
    }
}
