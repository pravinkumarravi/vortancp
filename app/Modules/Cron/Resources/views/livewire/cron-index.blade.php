<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Cron Jobs & Task Scheduler
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Automate periodic scripts, background commands, and maintenance jobs with granular Linux crontab syntax.
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

            <button wire:click="viewRawCrontab" class="inline-flex items-center px-3.5 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                View Raw Crontab
            </button>

            <button wire:click="$set('showCreateModal', true)" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Cron Job
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Scheduled Tasks</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $cronJobs->count() }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Schedules</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $cronJobs->where('is_active', true)->count() }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cron Daemon</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">cron.service active</p>
            </div>
        </div>
    </div>

    <!-- Cron Jobs Table -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Configured Tasks</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">Target Account: <strong class="text-gray-700 dark:text-gray-300">{{ $account?->username ?? 'N/A' }}</strong></span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Schedule</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Command</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($cronJobs as $job)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $job->expression }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-mono text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900/60 px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 max-w-md truncate">
                                    {{ $job->command }}
                                </div>
                                @if($job->email_notification)
                                    <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        Logs sent to {{ $job->email_notification }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $job->description ?: '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        Disabled
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button wire:click="openEditModal({{ $job->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 text-xs">
                                    Edit
                                </button>
                                <button wire:click="toggleStatus({{ $job->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 text-xs">
                                    {{ $job->is_active ? 'Disable' : 'Enable' }}
                                </button>
                                <button wire:confirm="Are you sure you want to delete this scheduled task?" wire:click="deleteJob({{ $job->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 text-xs">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 font-medium text-gray-900 dark:text-white">No scheduled cron jobs</p>
                                <p class="mt-1 text-gray-500 dark:text-gray-400">Create scheduled jobs to run database backups, queue workers, or automated scripts.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Job Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Scheduled Task</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="createJob" class="mt-4 space-y-4">
                    <!-- Schedule Presets -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Schedule Presets</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button type="button" wire:click="applyPreset('every_minute')" class="px-2.5 py-1.5 text-xs text-left font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Every Minute (* * * * *)
                            </button>
                            <button type="button" wire:click="applyPreset('every_5_minutes')" class="px-2.5 py-1.5 text-xs text-left font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Every 5 Min (*/5 * * * *)
                            </button>
                            <button type="button" wire:click="applyPreset('every_hour')" class="px-2.5 py-1.5 text-xs text-left font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Once an Hour (0 * * * *)
                            </button>
                            <button type="button" wire:click="applyPreset('every_day_midnight')" class="px-2.5 py-1.5 text-xs text-left font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Daily Midnight (0 0 * * *)
                            </button>
                            <button type="button" wire:click="applyPreset('every_week_sunday')" class="px-2.5 py-1.5 text-xs text-left font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Weekly Sun (0 0 * * 0)
                            </button>
                            <button type="button" wire:click="applyPreset('every_month_first')" class="px-2.5 py-1.5 text-xs text-left font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Monthly 1st (0 0 1 * *)
                            </button>
                        </div>
                    </div>

                    <!-- 5-Field Cron Grid -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Timing Fields</label>
                        <div class="grid grid-cols-5 gap-2">
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Minute</span>
                                <input type="text" wire:model="minute" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Hour</span>
                                <input type="text" wire:model="hour" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Day/Mo</span>
                                <input type="text" wire:model="day_of_month" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Month</span>
                                <input type="text" wire:model="month" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Weekday</span>
                                <input type="text" wire:model="day_of_week" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Command -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Command to Execute</label>
                        <input type="text" wire:model="command" placeholder="php /home/{{ $account?->username ?? 'user' }}/public_html/artisan schedule:run" class="w-full font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('command') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Description (Optional)</label>
                            <input type="text" wire:model="description" placeholder="Laravel Queue Worker" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Notification Email (Optional)</label>
                            <input type="email" wire:model="email_notification" placeholder="ops@{{ $account?->primary_domain ?? 'domain.com' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            Schedule Job
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit Job Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Scheduled Task</h3>
                    <button wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="updateJob" class="mt-4 space-y-4">
                    <!-- 5-Field Cron Grid -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Timing Fields</label>
                        <div class="grid grid-cols-5 gap-2">
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Minute</span>
                                <input type="text" wire:model="minute" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Hour</span>
                                <input type="text" wire:model="hour" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Day/Mo</span>
                                <input type="text" wire:model="day_of_month" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Month</span>
                                <input type="text" wire:model="month" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mb-1">Weekday</span>
                                <input type="text" wire:model="day_of_week" class="w-full text-center font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Command -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Command to Execute</label>
                        <input type="text" wire:model="command" class="w-full font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('command') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Description (Optional)</label>
                            <input type="text" wire:model="description" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Notification Email (Optional)</label>
                            <input type="email" wire:model="email_notification" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Raw Crontab Modal -->
    @if($showRawCrontabModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Compiled Crontab File</h3>
                    <button wire:click="$set('showRawCrontabModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        Location: <code class="font-mono text-indigo-600 dark:text-indigo-400">/var/spool/cron/crontabs/{{ $account?->username ?? 'user' }}</code>
                    </p>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-xl font-mono text-xs overflow-x-auto whitespace-pre leading-relaxed border border-gray-800">{{ $rawCrontab }}</pre>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700 mt-4">
                    <button type="button" wire:click="$set('showRawCrontabModal', false)" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
