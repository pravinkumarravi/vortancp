<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Server Migration & Archive Importer
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Automated cPanel cpmove archive restoration, Plesk backup decoding, and live host-to-host account migration.
            </p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Total Migrations</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold">Completed Accounts</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $metrics['completed'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-semibold">In Progress</div>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $metrics['migrating'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-rose-600 dark:text-rose-400 uppercase tracking-wider font-semibold">Failed / Warnings</div>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $metrics['failed'] }}</div>
        </div>
    </div>

    <!-- Migration Wizard Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-xs space-y-6">
        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Import cPanel (cpmove) or Plesk Backup Archive
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Enter the absolute filesystem path to a <code class="text-indigo-500 font-mono">cpmove-{user}.tar.gz</code> archive. Pre-flight checks will scan all domains, databases, mailboxes, and cron configurations.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Archive Path</label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model="archivePath"
                        placeholder="/var/vortan/backups/imports/cpmove-username.tar.gz"
                        class="flex-1 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    />
                    <button
                        wire:click="inspectArchive"
                        wire:loading.attr="disabled"
                        type="button"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50 shrink-0"
                    >
                        <span wire:loading.remove wire:target="inspectArchive">Pre-Flight Inspect</span>
                        <span wire:loading wire:target="inspectArchive">Analyzing...</span>
                    </button>
                </div>
                @error('archivePath') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Target Hosting Plan</label>
                <select
                    wire:model="selectedPlanId"
                    class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                    <option value="">Auto-select / Standard Plan</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->max_disk_mb }} MB Disk)</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Pre-Flight Inspection Results Checklist -->
        @if($preflightData)
            <div class="rounded-xl border {{ $preflightData['valid'] ? 'border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/20' : 'border-rose-300 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/20' }} p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        @if($preflightData['valid'])
                            <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Pre-Flight Inspection Passed: Ready for Import
                        @else
                            <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Pre-Flight Inspection Failed
                        @endif
                    </h3>
                    <span class="text-xs font-mono text-gray-500 uppercase">{{ $preflightData['source_type'] }} Format</span>
                </div>

                @if(!empty($preflightData['warnings']))
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-800 dark:text-amber-300">
                        @foreach($preflightData['warnings'] as $w)
                            <div>⚠️ {{ $w }}</div>
                        @endforeach
                    </div>
                @endif

                @if($preflightData['valid'])
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400 block font-medium">Primary Domain</span>
                            <span class="font-bold text-gray-900 dark:text-white font-mono mt-0.5 block">{{ $preflightData['primary_domain'] }}</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400 block font-medium">Databases Found</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400 mt-0.5 block">{{ count($preflightData['databases']) }} schemas</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400 block font-medium">Mailboxes Found</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5 block">{{ count($preflightData['mailboxes']) }} mailboxes</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400 block font-medium">Scheduled Crons</span>
                            <span class="font-bold text-gray-900 dark:text-white mt-0.5 block">{{ count($preflightData['cron_jobs']) }} jobs</span>
                        </div>
                    </div>

                    <!-- Target Overrides & Submit -->
                    <div class="pt-2 flex flex-col sm:flex-row items-end justify-between gap-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1 w-full">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Target Account Username</label>
                                <input
                                    type="text"
                                    wire:model="targetUsername"
                                    class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs text-gray-900 dark:text-white font-mono"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Target Primary Domain</label>
                                <input
                                    type="text"
                                    wire:model="targetDomain"
                                    class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs text-gray-900 dark:text-white font-mono"
                                />
                            </div>
                        </div>
                        <button
                            wire:click="startMigration"
                            wire:loading.attr="disabled"
                            type="button"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition flex items-center gap-1.5 shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                            <span wire:loading.remove wire:target="startMigration">Execute Full Migration</span>
                            <span wire:loading wire:target="startMigration">Restoring Assets...</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Migration Jobs History Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-xs">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Migration History & Logs</h2>
            <div class="flex items-center gap-2">
                <select wire:model.live="statusFilter" class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-1 text-xs text-gray-700 dark:text-gray-300">
                    <option value="all">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="migrating">In Progress</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 uppercase font-semibold border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Job ID</th>
                        <th class="px-4 py-3">Account & Domain</th>
                        <th class="px-4 py-3">Source Archive</th>
                        <th class="px-4 py-3">Progress / Step</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Migrated Assets</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($jobs as $job)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-4 py-3 font-mono text-gray-500">#{{ $job->id }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $job->target_domain }}</div>
                                <div class="text-[11px] font-mono text-gray-500">{{ $job->target_username }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">
                                <div class="truncate max-w-xs">{{ $job->archive_name }}</div>
                                <div class="text-[10px] text-gray-500">{{ $job->formatted_size }}</div>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="truncate text-gray-700 dark:text-gray-300 font-medium">{{ $job->current_step }}</span>
                                    <span class="font-mono text-gray-500 ml-1">{{ $job->step_progress }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $job->step_progress }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($job->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                        Completed
                                    </span>
                                @elseif($job->status === 'migrating')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-800">
                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Migrating
                                    </span>
                                @elseif($job->status === 'failed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-400 border border-rose-300 dark:border-rose-800">
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400">
                                        {{ ucfirst($job->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if(!empty($job->migrated_assets))
                                    <div class="text-[11px] text-gray-600 dark:text-gray-400 space-x-1">
                                        <span>🌐 {{ $job->migrated_assets['domains'] ?? 0 }} domains</span>
                                        <span>•</span>
                                        <span>🗄️ {{ $job->migrated_assets['databases'] ?? 0 }} DBs</span>
                                        <span>•</span>
                                        <span>✉️ {{ $job->migrated_assets['mailboxes'] ?? 0 }} mails</span>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-[11px]">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    wire:click="viewLogs({{ $job->id }})"
                                    type="button"
                                    class="px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
                                >
                                    Logs
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No migration jobs recorded yet. Input an archive path above to perform your first server import.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

    <!-- Event Logs Modal -->
    @if($viewingJob)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-2xl w-full overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Migration Log: Job #{{ $viewingJob->id }}</h3>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $viewingJob->target_domain }} ({{ $viewingJob->target_username }})</p>
                    </div>
                    <button wire:click="closeLogs" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-4 bg-gray-950 font-mono text-xs text-gray-300 max-h-96 overflow-y-auto space-y-1">
                    @forelse($viewingJob->log_messages ?? [] as $log)
                        <div class="flex items-start gap-2">
                            <span class="text-gray-500 shrink-0">[{{ $log['timestamp'] }}]</span>
                            <span class="{{ str_contains($log['message'], 'ERROR') ? 'text-rose-400' : (str_contains($log['message'], '100%') ? 'text-emerald-400 font-semibold' : 'text-gray-300') }}">{{ $log['message'] }}</span>
                        </div>
                    @empty
                        <div class="text-gray-500">No logs recorded for this job.</div>
                    @endforelse
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-right">
                    <button wire:click="closeLogs" type="button" class="px-4 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
