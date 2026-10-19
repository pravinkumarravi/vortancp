<?php

declare(strict_types=1);

namespace App\Modules\Cron\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Cron\Models\CronJob;
use App\Modules\Cron\Services\CronManagerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CronIndex extends Component
{
    public ?int $selectedAccountId = null;

    // Form fields
    public string $minute = '*';
    public string $hour = '*';
    public string $day_of_month = '*';
    public string $month = '*';
    public string $day_of_week = '*';
    public string $command = '';
    public string $description = '';
    public string $email_notification = '';

    // Modals
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showRawCrontabModal = false;

    public ?int $editing_job_id = null;
    public string $rawCrontab = '';

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

        return HostingAccount::with(['customer', 'server', 'cronJobs'])->find($this->selectedAccountId);
    }

    public function applyPreset(string $preset): void
    {
        match ($preset) {
            'every_minute' => [$this->minute, $this->hour, $this->day_of_month, $this->month, $this->day_of_week] = ['*', '*', '*', '*', '*'],
            'every_5_minutes' => [$this->minute, $this->hour, $this->day_of_month, $this->month, $this->day_of_week] = ['*/5', '*', '*', '*', '*'],
            'every_15_minutes' => [$this->minute, $this->hour, $this->day_of_month, $this->month, $this->day_of_week] = ['*/15', '*', '*', '*', '*'],
            'every_hour' => [$this->minute, $this->hour, $this->day_of_month, $this->month, $this->day_of_week] = ['0', '*', '*', '*', '*'],
            'every_day_midnight' => [$this->minute, $this->hour, $this->day_of_month, $this->month, $this->day_of_week] = ['0', '0', '*', '*', '*'],
            'every_week_sunday' => [$this->minute, $this->hour, $this->day_of_month, $this->month, $this->day_of_week] = ['0', '0', '*', '*', '0'],
            'every_month_first' => [$this->minute, $this->hour, $this->day_of_month, $this->month, $this->day_of_week] = ['0', '0', '1', '*', '*'],
            default => null,
        };
    }

    public function createJob(CronManagerService $cronService): void
    {
        $this->validate([
            'minute' => 'required|string',
            'hour' => 'required|string',
            'day_of_month' => 'required|string',
            'month' => 'required|string',
            'day_of_week' => 'required|string',
            'command' => 'required|string|min:2',
            'description' => 'nullable|string|max:255',
            'email_notification' => 'nullable|email',
        ]);

        $account = $this->account;
        if (! $account) {
            session()->flash('error', 'No active hosting account selected.');
            return;
        }

        try {
            $cronService->createJob(
                account: $account,
                schedule: [
                    'minute' => $this->minute,
                    'hour' => $this->hour,
                    'day_of_month' => $this->day_of_month,
                    'month' => $this->month,
                    'day_of_week' => $this->day_of_week,
                ],
                command: $this->command,
                description: $this->description ?: null,
                emailNotification: $this->email_notification ?: null
            );

            session()->flash('success', 'Cron job scheduled successfully.');
            $this->reset(['command', 'description', 'email_notification', 'showCreateModal']);
            $this->applyPreset('every_day_midnight');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openEditModal(int $id): void
    {
        $job = CronJob::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if (! $job) {
            return;
        }

        $this->editing_job_id = $job->id;
        $this->minute = $job->minute;
        $this->hour = $job->hour;
        $this->day_of_month = $job->day_of_month;
        $this->month = $job->month;
        $this->day_of_week = $job->day_of_week;
        $this->command = $job->command;
        $this->description = $job->description ?? '';
        $this->email_notification = $job->email_notification ?? '';
        $this->showEditModal = true;
    }

    public function updateJob(CronManagerService $cronService): void
    {
        $this->validate([
            'minute' => 'required|string',
            'hour' => 'required|string',
            'day_of_month' => 'required|string',
            'month' => 'required|string',
            'day_of_week' => 'required|string',
            'command' => 'required|string|min:2',
            'description' => 'nullable|string|max:255',
            'email_notification' => 'nullable|email',
        ]);

        $job = CronJob::where('hosting_account_id', $this->selectedAccountId)->find($this->editing_job_id);
        if (! $job) {
            session()->flash('error', 'Cron job not found.');
            return;
        }

        try {
            $cronService->updateJob(
                job: $job,
                schedule: [
                    'minute' => $this->minute,
                    'hour' => $this->hour,
                    'day_of_month' => $this->day_of_month,
                    'month' => $this->month,
                    'day_of_week' => $this->day_of_week,
                ],
                command: $this->command,
                description: $this->description ?: null,
                emailNotification: $this->email_notification ?: null
            );

            session()->flash('success', 'Cron job updated successfully.');
            $this->showEditModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleStatus(int $id, CronManagerService $cronService): void
    {
        $job = CronJob::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($job) {
            $cronService->toggleJob($job);
            session()->flash('success', 'Cron job status updated.');
        }
    }

    public function deleteJob(int $id, CronManagerService $cronService): void
    {
        $job = CronJob::where('hosting_account_id', $this->selectedAccountId)->find($id);
        if ($job) {
            $cronService->deleteJob($job);
            session()->flash('success', 'Cron job removed.');
        }
    }

    public function viewRawCrontab(CronManagerService $cronService): void
    {
        if ($this->account) {
            $this->rawCrontab = $cronService->getCompiledCrontab($this->account);
            $this->showRawCrontabModal = true;
        }
    }

    public function render()
    {
        $accounts = Auth::user()->hasRole('customer')
            ? []
            : HostingAccount::select('id', 'username', 'primary_domain')->get();

        $cronJobs = $this->account
            ? $this->account->cronJobs()->latest()->get()
            : collect();

        return view('cron::livewire.cron-index', [
            'account' => $this->account,
            'accounts' => $accounts,
            'cronJobs' => $cronJobs,
        ]);
    }
}
