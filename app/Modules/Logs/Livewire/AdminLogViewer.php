<?php

declare(strict_types=1);

namespace App\Modules\Logs\Livewire;

use App\Modules\Logs\Services\LogReaderService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('System Logs Inspector - VortanCP')]
class AdminLogViewer extends Component
{
    use AuthorizesRequests;

    public string $source = 'nginx-error';
    public int $lines = 100;
    public string $search = '';
    public string $level = 'all';
    public bool $autoRefresh = false;

    public function downloadLog(LogReaderService $reader): StreamedResponse
    {
        $this->authorize('servers.manage');

        $result = $reader->readSystemLog($this->source, 1000);
        $fileName = "vortan_{$this->source}_" . date('Ymd_His') . ".log";

        return response()->streamDownload(function () use ($result) {
            foreach ($result['entries'] as $entry) {
                echo $entry['raw'] . "\n";
            }
        }, $fileName, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function render(LogReaderService $reader): View
    {
        $this->authorize('servers.manage');

        $sources = $reader->getAdminLogSources();
        $logData = $reader->readSystemLog(
            source: $this->source,
            lines: $this->lines,
            search: $this->search,
            level: $this->level
        );

        return view('logs::livewire.admin-log-viewer', [
            'sources' => $sources,
            'logData' => $logData,
        ]);
    }
}
