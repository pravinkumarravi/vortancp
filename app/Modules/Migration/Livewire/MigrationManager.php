<?php

declare(strict_types=1);

namespace App\Modules\Migration\Livewire;

use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\Migration\Models\MigrationJob;
use App\Modules\Migration\Services\CpanelArchiveParserService;
use App\Modules\Migration\Services\MigrationExecutionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class MigrationManager extends Component
{
    public string $archivePath = '';
    public string $targetUsername = '';
    public string $targetDomain = '';
    public ?int $selectedPlanId = null;
    public string $statusFilter = 'all';

    public ?array $preflightData = null;
    public ?int $viewingJobId = null;

    public function inspectArchive(CpanelArchiveParserService $parser): void
    {
        $this->resetValidation();

        if (trim($this->archivePath) === '') {
            $this->addError('archivePath', 'Please specify an archive file path.');
            return;
        }

        $this->preflightData = $parser->inspectArchive(trim($this->archivePath));

        if ($this->preflightData['valid']) {
            if (!$this->targetUsername) {
                $this->targetUsername = $this->preflightData['username'];
            }
            if (!$this->targetDomain) {
                $this->targetDomain = $this->preflightData['primary_domain'];
            }
        }
    }

    public function startMigration(MigrationExecutionService $executionService): void
    {
        $this->resetValidation();

        if (trim($this->archivePath) === '') {
            $this->addError('archivePath', 'Please specify an archive file path.');
            return;
        }

        if (!File::exists(trim($this->archivePath))) {
            $this->addError('archivePath', 'Archive file does not exist at specified path.');
            return;
        }

        $job = MigrationJob::create([
            'user_id' => Auth::id(),
            'source_type' => 'cpanel',
            'archive_name' => basename($this->archivePath),
            'archive_path' => trim($this->archivePath),
            'archive_size_bytes' => File::size(trim($this->archivePath)),
            'target_username' => $this->targetUsername ?: 'migrated_user',
            'target_domain' => $this->targetDomain ?: 'migrated.local',
            'status' => MigrationJob::STATUS_PENDING,
            'step_progress' => 0,
            'current_step' => 'Queued for migration',
        ]);

        $executionService->runMigration($job, $this->selectedPlanId);

        session()->flash('success', "Migration job #{$job->id} completed with status: {$job->status}");

        $this->preflightData = null;
        $this->archivePath = '';
        $this->targetUsername = '';
        $this->targetDomain = '';
    }

    public function viewLogs(int $jobId): void
    {
        $this->viewingJobId = $jobId;
    }

    public function closeLogs(): void
    {
        $this->viewingJobId = null;
    }

    public function render(): View
    {
        $query = MigrationJob::query()->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $jobs = $query->paginate(10);
        $plans = HostingPlan::where('is_active', true)->get();

        $metrics = [
            'total' => MigrationJob::count(),
            'completed' => MigrationJob::where('status', MigrationJob::STATUS_COMPLETED)->count(),
            'migrating' => MigrationJob::where('status', MigrationJob::STATUS_MIGRATING)->count(),
            'failed' => MigrationJob::where('status', MigrationJob::STATUS_FAILED)->count(),
        ];

        $viewingJob = $this->viewingJobId ? MigrationJob::find($this->viewingJobId) : null;

        return view('migration::livewire.migration-manager', [
            'jobs' => $jobs,
            'plans' => $plans,
            'metrics' => $metrics,
            'viewingJob' => $viewingJob,
        ])->layout('layouts.app', [
            'title' => 'Server Migration & Importer',
        ]);
    }
}
