<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Backup & Disaster Recovery
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Create comprehensive snapshots, configure automated retention schedules, and restore website components with SHA-256 verified integrity.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(count($accounts) > 1)
                <select wire:model.live="selectedAccountId" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                    @endforeach
                </select>
            @endif

            <button wire:click="$set('showCreateScheduleModal', true)" class="inline-flex items-center px-3.5 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Schedule Policy
            </button>

            <button wire:click="$set('showCreateBackupModal', true)" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Backup Now
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/40 p-4 border border-green-200 dark:border-green-800 flex items-center justify-between">
            <div class="flex items-center text-sm font-medium text-green-800 dark:text-green-200">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/40 p-4 border border-red-200 dark:border-red-800 flex items-center justify-between">
            <div class="flex items-center text-sm font-medium text-red-800 dark:text-red-200">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Backups</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $backups->count() }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Backup Storage</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">
                    {{ round($backups->sum('size_bytes') / 1048576, 2) }} MB
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Schedules</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $schedules->where('is_active', true)->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'backups')" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'backups' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Backup Snapshots ({{ $backups->count() }})
            </button>
            <button wire:click="$set('activeTab', 'schedules')" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'schedules' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Automated Policies ({{ $schedules->count() }})
            </button>
        </nav>
    </div>

    @if($activeTab === 'backups')
        <!-- Backups Table -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Generated Backup Archives</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Account: <strong class="text-gray-700 dark:text-gray-300">{{ $account?->username ?? 'N/A' }}</strong></span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Archive Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($backups as $b)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-mono text-xs mr-3">
                                            TAR
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white font-mono text-xs">{{ $b->filename }}</div>
                                            <div class="text-[11px] text-gray-400 dark:text-gray-500 font-mono">SHA256: {{ substr($b->checksum_sha256 ?? 'pending', 0, 16) }}...</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 capitalize">
                                        {{ str_replace('_', ' ', $b->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 font-mono text-xs">
                                    {{ $b->formatted_size }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($b->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                            Completed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                            {{ ucfirst($b->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $b->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button wire:click="viewManifest({{ $b->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 text-xs">
                                        Manifest
                                    </button>
                                    <button wire:click="openRestoreModal({{ $b->id }})" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 text-xs">
                                        Restore
                                    </button>
                                    <button wire:confirm="Are you sure you want to delete this backup archive?" wire:click="deleteBackup({{ $b->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 text-xs">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <p class="mt-2 font-medium text-gray-900 dark:text-white">No backups available</p>
                                    <p class="mt-1 text-gray-500 dark:text-gray-400">Create your first account backup snapshot or configure an automated policy.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Schedules Table -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Automated Schedule Policies</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Frequency</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Retention</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Components Included</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($schedules as $sched)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 capitalize">
                                        {{ $sched->frequency }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 text-xs">
                                    Keep last {{ $sched->retention_count }} copies
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs space-x-1">
                                    @if($sched->include_files) <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Files</span> @endif
                                    @if($sched->include_databases) <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Databases</span> @endif
                                    @if($sched->include_emails) <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Emails</span> @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($sched->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Paused</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button wire:click="toggleSchedule({{ $sched->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 text-xs">
                                        {{ $sched->is_active ? 'Pause' : 'Resume' }}
                                    </button>
                                    <button wire:confirm="Are you sure you want to delete this schedule policy?" wire:click="deleteSchedule({{ $sched->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 text-xs">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <p class="font-medium text-gray-900 dark:text-white">No automated schedules configured</p>
                                    <p class="mt-1 text-gray-500 dark:text-gray-400">Set up a daily or weekly retention schedule to automatically safeguard your account data.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Create Backup Modal -->
    @if($showCreateBackupModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create Account Backup Snapshot</h3>
                    <button wire:click="$set('showCreateBackupModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="generateBackup" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Backup Scope</label>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input type="radio" wire:model="backup_type" value="full" class="text-indigo-600 focus:ring-indigo-500">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Full Backup (Recommended)</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Includes all web files, databases, mailboxes, DNS zones, and cron tasks.</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input type="radio" wire:model="backup_type" value="files_only" class="text-indigo-600 focus:ring-indigo-500">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Website Files Only</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Archive home directory and document roots.</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input type="radio" wire:model="backup_type" value="database_only" class="text-indigo-600 focus:ring-indigo-500">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">MySQL Databases Only</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Export SQL dumps of all relational databases.</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input type="radio" wire:model="backup_type" value="email_only" class="text-indigo-600 focus:ring-indigo-500">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Mailboxes & Email Only</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Archive mail domain configuration and messages.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showCreateBackupModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            Generate Snapshot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Create Schedule Modal -->
    @if($showCreateScheduleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">New Automated Policy</h3>
                    <button wire:click="$set('showCreateScheduleModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="createSchedule" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Execution Frequency</label>
                        <select wire:model="sched_frequency" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="daily">Daily (Nightly at Midnight)</option>
                            <option value="weekly">Weekly (Sundays)</option>
                            <option value="monthly">Monthly (1st of Month)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Retention Count</label>
                        <input type="number" wire:model="sched_retention" min="1" max="100" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Number of completed copies to retain before pruning oldest.</p>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="sched_include_files" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                            Include Web Files
                        </label>
                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="sched_include_databases" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                            Include Relational Databases
                        </label>
                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="sched_include_emails" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                            Include Email Accounts
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showCreateScheduleModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            Save Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Restore Modal -->
    @if($showRestoreModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Restore from Snapshot</h3>
                    <button wire:click="$set('showRestoreModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="performRestore" class="mt-4 space-y-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-lg border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200">
                        <strong>Warning:</strong> Restoring will overwrite existing files or database tables matching the snapshot content.
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="restore_files" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                            Restore Website Files
                        </label>
                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="restore_databases" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                            Restore Databases
                        </label>
                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="restore_emails" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                            Restore Mailboxes
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showRestoreModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-sm">
                            Proceed with Restore
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Manifest Modal -->
    @if($showManifestModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Archive Manifest Metadata</h3>
                    <button wire:click="$set('showManifestModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4">
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-xl font-mono text-xs overflow-x-auto whitespace-pre leading-relaxed border border-gray-800">{{ json_encode($viewing_manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700 mt-4">
                    <button type="button" wire:click="$set('showManifestModal', false)" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
