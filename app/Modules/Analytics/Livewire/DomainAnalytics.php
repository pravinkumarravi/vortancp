<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Livewire;

use App\Modules\Analytics\Services\AccessLogParserService;
use App\Modules\Domains\Models\Domain;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DomainAnalytics extends Component
{
    public ?int $selectedDomainId = null;
    public int $timeframe = 7;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->hasRole('customer')) {
            $account = $user->customer?->hostingAccounts()->first();
            $firstDomain = $account ? $account->domains()->first() : null;
            $this->selectedDomainId = $firstDomain?->id;
        } else {
            $firstDomain = Domain::first();
            $this->selectedDomainId = $firstDomain?->id;
        }
    }

    public function generateSampleTraffic(AccessLogParserService $parser): void
    {
        if ($this->selectedDomainId) {
            $domain = Domain::find($this->selectedDomainId);
            if ($domain) {
                $parser->seedSampleMetrics($domain, $this->timeframe);
                session()->flash('success', "Synthesized sample traffic metrics for [{$domain->domain}].");
            }
        }
    }

    public function render(AccessLogParserService $parser): View
    {
        $user = Auth::user();

        if ($user && $user->hasRole('customer')) {
            $account = $user->customer?->hostingAccounts()->first();
            $domains = $account ? $account->domains()->get() : collect();
        } else {
            $domains = Domain::all();
        }

        // If no metrics yet and a domain exists, auto-seed for immediate visualization
        if ($this->selectedDomainId) {
            $domain = Domain::find($this->selectedDomainId);
            if ($domain && $domain->trafficMetrics()->count() === 0) {
                $parser->seedSampleMetrics($domain, 7);
            }
        }

        $analytics = $parser->getAggregateMetrics($this->selectedDomainId, $this->timeframe);

        $totalReq = max(1, $analytics['total_requests']);
        $statusPercent = [
            '2xx' => round(($analytics['status_2xx'] / $totalReq) * 100, 1),
            '3xx' => round(($analytics['status_3xx'] / $totalReq) * 100, 1),
            '4xx' => round(($analytics['status_4xx'] / $totalReq) * 100, 1),
            '5xx' => round(($analytics['status_5xx'] / $totalReq) * 100, 1),
        ];

        // Format bandwidth
        $bytes = $analytics['bandwidth_bytes'];
        $formattedBandwidth = $bytes >= 1073741824
            ? number_format($bytes / 1073741824, 2) . ' GB'
            : number_format($bytes / 1048576, 1) . ' MB';

        return view('analytics::livewire.domain-analytics', [
            'domains' => $domains,
            'analytics' => $analytics,
            'statusPercent' => $statusPercent,
            'formattedBandwidth' => $formattedBandwidth,
        ])->layout('layouts.app', [
            'title' => 'HTTP Traffic & Bandwidth Analytics',
        ]);
    }
}
