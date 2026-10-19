<div class="space-y-6">
    <!-- Header with Breadcrumbs & Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <span class="p-2 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </span>
                Metrics & Resource Utilization
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Real-time telemetry, node hardware utilization, load averages, and account quota metrics.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($isAdmin && count($servers) > 0)
                <div class="flex items-center gap-2">
                    <label for="srv_select" class="text-xs font-semibold text-gray-500 uppercase">Server:</label>
                    <select id="srv_select" wire:model.live="selectedServerId"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm py-2 px-3 focus:ring-teal-500 focus:border-teal-500 dark:text-gray-200">
                        @foreach($servers as $srv)
                            <option value="{{ $srv->id }}">{{ $srv->name }} ({{ $srv->ip_address }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(count($accounts) > 0)
                <div class="flex items-center gap-2">
                    <label for="acc_select" class="text-xs font-semibold text-gray-500 uppercase">Account:</label>
                    <select id="acc_select" wire:model.live="selectedAccountId"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm py-2 px-3 focus:ring-teal-500 focus:border-teal-500 dark:text-gray-200">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($isAdmin)
                <button wire:click="probeMetrics"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg shadow-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Probe Now
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-200">&times;</button>
        </div>
    @endif

    <!-- ADMIN: LIVE NODE HARDWARE GAUGES -->
    @if($isAdmin)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- CPU Utilization -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">CPU Utilization</span>
                    <span class="text-xs px-2 py-0.5 rounded bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 font-mono">
                        Load: {{ number_format($liveNodeStats['load_1min'], 2) }}
                    </span>
                </div>
                <div class="text-3xl font-extrabold text-gray-900 dark:text-white">
                    {{ $liveNodeStats['cpu_pct'] }}%
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 mt-4 overflow-hidden">
                    <div class="bg-teal-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $liveNodeStats['cpu_pct']) }}%"></div>
                </div>
            </div>

            <!-- Memory Usage -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">RAM Usage</span>
                    <span class="text-xs text-gray-400">{{ $liveNodeStats['memory_used_mb'] }} / {{ $liveNodeStats['memory_total_mb'] }} MB</span>
                </div>
                <div class="text-3xl font-extrabold text-gray-900 dark:text-white">
                    {{ $liveNodeStats['memory_pct'] }}%
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 mt-4 overflow-hidden">
                    <div class="bg-indigo-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $liveNodeStats['memory_pct']) }}%"></div>
                </div>
            </div>

            <!-- Root Disk Storage -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Disk Storage</span>
                    <span class="text-xs text-gray-400">{{ round($liveNodeStats['disk_used_mb'] / 1024, 1) }} / {{ round($liveNodeStats['disk_total_mb'] / 1024, 1) }} GB</span>
                </div>
                <div class="text-3xl font-extrabold text-gray-900 dark:text-white">
                    {{ $liveNodeStats['disk_pct'] }}%
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 mt-4 overflow-hidden">
                    <div class="bg-amber-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $liveNodeStats['disk_pct']) }}%"></div>
                </div>
            </div>

            <!-- Node Health Status -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-3">Node Status</span>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Operational</span>
                    </div>
                </div>
                <span class="text-xs text-gray-400 mt-4">
                    Host: {{ $server->hostname ?? 'localhost' }}
                </span>
            </div>
        </div>
    @endif

    <!-- ACCOUNT LEVEL QUOTA UTILIZATION -->
    @if($accountReport)
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700/60 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Hosting Account Quota Consumption
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Account: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $account->username }}</span> (Plan: {{ $account->subscription?->hostingPlan?->name ?? 'Standard' }})
                    </p>
                </div>

                @if($accountReport['is_critical'])
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                        Critical: Quota Limit Exceeded
                    </span>
                @elseif($accountReport['is_warning'])
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                        Warning: High Resource Usage (>80%)
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                        Within Allocated Quota
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Account Disk Quota -->
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                    <div class="flex justify-between text-xs mb-2">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Disk Quota</span>
                        <span class="text-gray-500">{{ $accountReport['disk_used_mb'] }} MB / {{ $accountReport['disk_max_mb'] }} MB</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full {{ $accountReport['disk_pct'] > 90 ? 'bg-rose-500' : ($accountReport['disk_pct'] > 75 ? 'bg-amber-500' : 'bg-teal-500') }}"
                            style="width: {{ $accountReport['disk_pct'] }}%"></div>
                    </div>
                    <div class="mt-2 text-right text-xs font-bold text-gray-700 dark:text-gray-300">{{ $accountReport['disk_pct'] }}%</div>
                </div>

                <!-- Account Bandwidth Quota -->
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                    <div class="flex justify-between text-xs mb-2">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Bandwidth (Monthly)</span>
                        <span class="text-gray-500">{{ $accountReport['bandwidth_used_mb'] }} MB / {{ $accountReport['bandwidth_max_mb'] }} MB</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full {{ $accountReport['bandwidth_pct'] > 90 ? 'bg-rose-500' : ($accountReport['bandwidth_pct'] > 75 ? 'bg-amber-500' : 'bg-indigo-500') }}"
                            style="width: {{ $accountReport['bandwidth_pct'] }}%"></div>
                    </div>
                    <div class="mt-2 text-right text-xs font-bold text-gray-700 dark:text-gray-300">{{ $accountReport['bandwidth_pct'] }}%</div>
                </div>

                <!-- Account Inode Counter -->
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800 flex flex-col justify-between">
                    <div class="flex justify-between text-xs">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">File Inodes Used</span>
                        <span class="text-teal-600 dark:text-teal-400 font-semibold font-mono">{{ number_format($accountReport['inodes_used']) }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        Total number of filesystem files and directories stored within this hosting jail.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- HISTORICAL READINGS TABLE -->
    @if($isAdmin && $serverHistory->isNotEmpty())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/60">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Recent Node Telemetry Snapshots</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900/50 uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Timestamp</th>
                        <th class="px-6 py-3">CPU %</th>
                        <th class="px-6 py-3">Memory %</th>
                        <th class="px-6 py-3">Disk %</th>
                        <th class="px-6 py-3">Load (1 min)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60 font-mono">
                    @foreach($serverHistory as $snap)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-6 py-3 text-gray-500 font-sans">{{ $snap->recorded_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-6 py-3 font-semibold text-teal-600">{{ $snap->cpu_pct }}%</td>
                            <td class="px-6 py-3 text-indigo-600">{{ $snap->memory_pct }}%</td>
                            <td class="px-6 py-3 text-amber-600">{{ $snap->disk_pct }}%</td>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">{{ number_format($snap->load_1min, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
