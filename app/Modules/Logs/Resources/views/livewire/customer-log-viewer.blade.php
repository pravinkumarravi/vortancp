<div class="space-y-6" @if ($autoRefresh) wire:poll.5s @endif>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Domain Access & Error Logs</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Live HTTP visitor traffic, user agents, status codes, and PHP runtime error logs for your hosted websites.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button wire:click="$toggle('autoRefresh')"
                    class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg border transition {{ $autoRefresh ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <span class="w-2 h-2 mr-2 rounded-full {{ $autoRefresh ? 'bg-emerald-500 animate-ping' : 'bg-gray-400' }}"></span>
                {{ $autoRefresh ? 'Streaming Active' : 'Live Stream' }}
            </button>
            <button wire:click="downloadLog"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download (.log)
            </button>
        </div>
    </div>

    <!-- Domain Selector & Log Type Toggle Bar -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Domain Dropdown -->
            <div class="flex items-center space-x-3">
                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Domain:</label>
                <select wire:model.live="selectedDomain" class="text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-gray-900 dark:text-white font-medium">
                    @forelse ($domains as $d)
                        <option value="{{ $d->domain }}">{{ $d->domain }}</option>
                    @empty
                        <option value="{{ $account?->primary_domain }}">{{ $account?->primary_domain ?? 'default' }}</option>
                    @endforelse
                </select>
            </div>

            <!-- Access vs Error Toggle -->
            <div class="inline-flex rounded-lg p-1 bg-gray-100 dark:bg-gray-700/60">
                <button wire:click="$set('type', 'access')"
                        class="px-4 py-1.5 text-xs font-semibold rounded-md transition {{ $type === 'access' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">
                    HTTP Access Log
                </button>
                <button wire:click="$set('type', 'error')"
                        class="px-4 py-1.5 text-xs font-semibold rounded-md transition {{ $type === 'error' ? 'bg-white dark:bg-gray-800 text-rose-600 dark:text-rose-400 shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">
                    Web / PHP Error Log
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <!-- Keyword Search -->
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Filter requests, IP, URL path..."
                       class="w-full text-xs bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg pl-8 pr-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Lines Count -->
            <div class="flex items-center justify-end space-x-2">
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Tail Lines:</label>
                <select wire:model.live="lines" class="text-xs bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-2 text-gray-900 dark:text-white">
                    <option value="50">Last 50 entries</option>
                    <option value="100">Last 100 entries</option>
                    <option value="250">Last 250 entries</option>
                    <option value="500">Last 500 entries</option>
                </select>
                <span class="text-xs text-gray-400 pl-2">Total: {{ $logData['total'] }}</span>
            </div>
        </div>
    </div>

    <!-- Terminal Style Log Viewer Stream -->
    <div class="bg-gray-950 border border-gray-800 rounded-xl overflow-hidden shadow-md">
        <div class="bg-gray-900/90 px-4 py-2 border-b border-gray-800 flex items-center justify-between text-xs text-gray-400">
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                <span class="ml-2 font-mono text-gray-300">{{ $selectedDomain }}.{{ $type }}.log</span>
            </div>
            <div class="font-mono text-gray-400 text-xs">
                Mode: {{ strtoupper($type) }}
            </div>
        </div>

        <div class="p-4 overflow-x-auto max-h-[550px] overflow-y-auto space-y-1 font-mono text-xs leading-relaxed">
            @forelse ($logData['entries'] as $idx => $entry)
                <div class="flex items-start space-x-3 py-1 px-2 rounded hover:bg-gray-900/60 transition group">
                    <span class="text-gray-600 select-none w-8 text-right shrink-0">{{ $idx + 1 }}</span>
                    <span class="text-gray-500 shrink-0 text-xs">{{ $entry['timestamp'] }}</span>

                    @if ($entry['status_code'])
                        @if ($entry['status_code'] >= 500)
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800 shrink-0">
                                {{ $entry['status_code'] }}
                            </span>
                        @elseif ($entry['status_code'] >= 400)
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800 shrink-0">
                                {{ $entry['status_code'] }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800 shrink-0">
                                {{ $entry['status_code'] }}
                            </span>
                        @endif
                    @endif

                    @if ($entry['client_ip'])
                        <span class="text-indigo-400 shrink-0">{{ $entry['client_ip'] }}</span>
                    @endif

                    <span class="text-gray-200 break-all flex-1">{{ $entry['message'] }}</span>
                </div>
            @empty
                <div class="py-12 text-center text-gray-500">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p>No log records recorded for {{ $selectedDomain }} matching the search criteria.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
