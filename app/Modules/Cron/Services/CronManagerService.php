<?php

declare(strict_types=1);

namespace App\Modules\Cron\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Cron\Models\CronJob;
use App\Modules\HostingPlans\Services\EntitlementService;

class CronManagerService
{
    public function __construct(
        protected CrontabCompilerService $compiler,
        protected EntitlementService $entitlementService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create a scheduled cron job.
     */
    public function createJob(
        HostingAccount $account,
        array $schedule,
        string $command,
        ?string $description = null,
        ?string $emailNotification = null
    ): CronJob {
        $this->entitlementService->assertCan($account, 'cron', $account->cronJobs()->count());

        $minute = $schedule['minute'] ?? '*';
        $hour = $schedule['hour'] ?? '*';
        $dom = $schedule['day_of_month'] ?? '*';
        $month = $schedule['month'] ?? '*';
        $dow = $schedule['day_of_week'] ?? '*';

        $this->compiler->validateSchedule($minute, $hour, $dom, $month, $dow);
        $cleanCommand = $this->compiler->sanitizeCommand($command);

        $cronJob = CronJob::create([
            'hosting_account_id' => $account->id,
            'minute' => $minute,
            'hour' => $hour,
            'day_of_month' => $dom,
            'month' => $month,
            'day_of_week' => $dow,
            'command' => $cleanCommand,
            'description' => $description,
            'email_notification' => $emailNotification,
            'is_active' => true,
        ]);

        $this->auditLogger->log(
            event: 'cron.job_created',
            auditable: $cronJob,
            oldValues: null,
            newValues: [
                'command' => $cleanCommand,
                'schedule' => $cronJob->expression,
            ]
        );

        return $cronJob;
    }

    /**
     * Update an existing cron job.
     */
    public function updateJob(
        CronJob $job,
        array $schedule,
        string $command,
        ?string $description = null,
        ?string $emailNotification = null
    ): CronJob {
        $minute = $schedule['minute'] ?? $job->minute;
        $hour = $schedule['hour'] ?? $job->hour;
        $dom = $schedule['day_of_month'] ?? $job->day_of_month;
        $month = $schedule['month'] ?? $job->month;
        $dow = $schedule['day_of_week'] ?? $job->day_of_week;

        $this->compiler->validateSchedule($minute, $hour, $dom, $month, $dow);
        $cleanCommand = $this->compiler->sanitizeCommand($command);

        $oldValues = [
            'command' => $job->command,
            'schedule' => $job->expression,
        ];

        $job->update([
            'minute' => $minute,
            'hour' => $hour,
            'day_of_month' => $dom,
            'month' => $month,
            'day_of_week' => $dow,
            'command' => $cleanCommand,
            'description' => $description,
            'email_notification' => $emailNotification,
        ]);

        $this->auditLogger->log(
            event: 'cron.job_updated',
            auditable: $job,
            oldValues: $oldValues,
            newValues: [
                'command' => $cleanCommand,
                'schedule' => $job->expression,
            ]
        );

        return $job;
    }

    /**
     * Toggle job active status.
     */
    public function toggleJob(CronJob $job): void
    {
        $oldStatus = $job->is_active;
        $job->update([
            'is_active' => ! $job->is_active,
        ]);

        $this->auditLogger->log(
            event: 'cron.job_toggled',
            auditable: $job,
            oldValues: ['is_active' => $oldStatus],
            newValues: ['is_active' => $job->is_active]
        );
    }

    /**
     * Delete a scheduled cron task.
     */
    public function deleteJob(CronJob $job): void
    {
        $this->auditLogger->log(
            event: 'cron.job_deleted',
            auditable: $job,
            oldValues: [
                'command' => $job->command,
                'schedule' => $job->expression,
            ],
            newValues: null
        );

        $job->delete();
    }

    /**
     * Get compiled crontab file string for account.
     */
    public function getCompiledCrontab(HostingAccount $account): string
    {
        return $this->compiler->compileUserCrontab($account);
    }
}
