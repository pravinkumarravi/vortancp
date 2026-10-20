<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Service Manager & Daemons</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Real-time status monitoring, 1-click service control, and automated crash recovery watchdog.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button wire:click="triggerWatchdog" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition disabled:opacity-50">
                <svg class="w-4 h-4 mr-2 animate-spin-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span wire:loading.remove wire:target="triggerWatchdog">Run Watchdog Probe</span>
                <span wire:loading wire:target="triggerWatchdog">Probing Daemons...</span>
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-800 dark:text-emerald-300 flex items-center space-x-2">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-xl text-sm text-rose-800 dark:text-rose-300 flex items-center space-x-2">
            <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Health Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Total Daemons</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $services->count() }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold">Operational (Running)</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $runningCount }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">Stopped / Inactive</div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $stoppedCount }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-rose-600 dark:text-rose-400 uppercase tracking-wider font-semibold">Failed / Alert</div>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $failedCount }}</div>
        </div>
    </div>

    <!-- Search & Category Filters -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="relative w-full max-w-sm">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search services (e.g. nginx, mysql, bind)..."
                   class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg pl-9 pr-4 py-2 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div class="flex flex-wrap gap-1.5 text-xs">
            @foreach (['all' => 'All Categories', 'web' => 'Web', 'php' => 'PHP', 'database' => 'Database', 'dns' => 'DNS', 'mail' => 'Mail', 'ftp' => 'FTP', 'security' => 'Security', 'agent' => 'Agent'] as $cat => $label)
                <button wire:click="$set('categoryFilter', '{{ $cat }}')"
                        class="px-3 py-1.5 rounded-lg font-medium transition {{ $categoryFilter === $cat ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Services Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                        <th class="py-3.5 px-4">Daemon & Service</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Watchdog</th>
                        <th class="py-3.5 px-4 text-center">Auto-Restart</th>
                        <th class="py-3.5 px-4">Last Activity</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($services as $service)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $service->display_name }}</div>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $service->name }}.service</span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="text-xs text-gray-400">{{ $service->description }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wide bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    {{ $service->category }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($service->status === 'running')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active (Running)
                                    </span>
                                @elseif ($service->status === 'stopped')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        Stopped
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button wire:click="toggleMonitoring({{ $service->id }})"
                                        class="text-xs font-semibold px-2 py-1 rounded transition {{ $service->is_monitored ? 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300' }}">
                                    {{ $service->is_monitored ? 'Enabled' : 'Disabled' }}
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button wire:click="toggleAutoRestart({{ $service->id }})"
                                        class="text-xs font-semibold px-2 py-1 rounded transition {{ $service->auto_restart ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300' }}">
                                    {{ $service->auto_restart ? 'Auto-Heal' : 'Manual' }}
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-500 dark:text-gray-400">
                                <div>Checked: {{ $service->last_checked_at ? $service->last_checked_at->diffForHumans() : 'Never' }}</div>
                                @if ($service->restart_count > 0)
                                    <div class="text-xs text-gray-400 mt-0.5">Restarts: {{ $service->restart_count }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <button wire:click="restart('{{ $service->name }}')" title="Restart Service"
                                            class="px-2.5 py-1 text-xs font-semibold rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 transition">
                                        Restart
                                    </button>
                                    <button wire:click="reload('{{ $service->name }}')" title="Soft Reload Configuration"
                                            class="px-2 py-1 text-xs font-semibold rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        Reload
                                    </button>
                                    @if ($service->status === 'running')
                                        <button wire:click="stop('{{ $service->name }}')" wire:confirm="Are you sure you want to stop {{ $service->display_name }}?" title="Stop Service"
                                                class="px-2 py-1 text-xs font-semibold rounded-md bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900 transition">
                                            Stop
                                        </button>
                                    @else
                                        <button wire:click="start('{{ $service->name }}')" title="Start Service"
                                                class="px-2 py-1 text-xs font-semibold rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 transition">
                                            Start
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                No system services match your search criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
