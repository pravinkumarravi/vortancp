<?php

declare(strict_types=1);

namespace App\Modules\Logs\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Logs\Services\LogReaderService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app', ['panel' => 'customer'])]
#[Title('Domain Access & Error Logs - VortanCP')]
class CustomerLogViewer extends Component
{
    use AuthorizesRequests;

    public string $selectedDomain = '';
    public string $type = 'access'; // access or error
    public int $lines = 100;
    public string $search = '';
    public bool $autoRefresh = false;

    public function mount(): void
    {
        $account = $this->getHostingAccount();
        if ($account && empty($this->selectedDomain)) {
            $this->selectedDomain = $account->primary_domain;
        }
    }

    protected function getHostingAccount(): ?HostingAccount
    {
        $user = auth()->user();

        return HostingAccount::whereHas('customer', function ($q) use ($user) {
            $q->where('user_id', $user?->id);
        })->first() ?? HostingAccount::first();
    }

    public function downloadLog(LogReaderService $reader): StreamedResponse
    {
        $account = $this->getHostingAccount();
        $username = $account?->username ?? 'nobody';
        $domain = $this->selectedDomain ?: ($account?->primary_domain ?? 'default');

        $result = $reader->readDomainLog(
            username: $username,
            domain: $domain,
            type: $this->type,
            lines: 1000
        );

        $fileName = "{$domain}_{$this->type}_" . date('Ymd_His') . ".log";

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
        $account = $this->getHostingAccount();
        $domains = $account ? Domain::where('hosting_account_id', $account->id)->get() : collect();

        if (empty($this->selectedDomain) && $domains->isNotEmpty()) {
            $this->selectedDomain = $domains->first()->domain;
        }

        $username = $account?->username ?? 'nobody';
        $domain = $this->selectedDomain ?: 'default';

        $logData = $reader->readDomainLog(
            username: $username,
            domain: $domain,
            type: $this->type,
            lines: $this->lines,
            search: $this->search
        );

        return view('logs::livewire.customer-log-viewer', [
            'domains' => $domains,
            'logData' => $logData,
            'account' => $account,
        ]);
    }
}
