<div class="space-y-6" @if ($autoRefresh) wire:poll.5s @endif>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">System Logs Inspector</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Real-time inspection and diagnostic streaming for web, database, mail, security, and agent daemons.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button wire:click="$toggle('autoRefresh')"
                    class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-lg border transition {{ $autoRefresh ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <span class="w-2 h-2 mr-2 rounded-full {{ $autoRefresh ? 'bg-emerald-500 animate-ping' : 'bg-gray-400' }}"></span>
                {{ $autoRefresh ? 'Live Polling Active (5s)' : 'Enable Live Stream' }}
            </button>
            <button wire:click="downloadLog"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Log (.log)
            </button>
        </div>
    </div>

    <!-- Log Source Selection Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
        @foreach ($sources as $key => $meta)
            <button wire:click="$set('source', '{{ $key }}')"
                    class="px-3.5 py-2 rounded-lg text-xs font-semibold transition flex items-center space-x-2 {{ $source === $key ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                <span>{{ $meta['name'] }}</span>
            </button>
        @endforeach
    </div>

    <!-- Active Source Metadata Bar & Filters -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <div class="flex items-center space-x-2">
                <span class="font-semibold text-gray-700 dark:text-gray-300">File Path:</span>
                <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-800 dark:text-gray-200">{{ $logData['file_path'] }}</span>
                <span class="text-gray-400">•</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $sources[$source]['description'] ?? '' }}</span>
            </div>
            <div class="text-gray-500 dark:text-gray-400">
                Displaying <strong>{{ $logData['total'] }}</strong> entries
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
            <!-- Keyword Search -->
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search log entries, IP, keyword..."
                       class="w-full text-xs bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg pl-8 pr-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Severity Level Filter -->
            <div class="flex items-center space-x-2">
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Severity:</label>
                <select wire:model.live="level" class="flex-1 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-2 text-gray-900 dark:text-white">
                    <option value="all">All Levels</option>
                    <option value="CRITICAL">Critical / Emergency</option>
                    <option value="ERROR">Errors</option>
                    <option value="WARNING">Warnings</option>
                    <option value="NOTICE">Notices</option>
                    <option value="INFO">Info</option>
                    <option value="DEBUG">Debug</option>
                </select>
            </div>

            <!-- Tail Lines Selector -->
            <div class="flex items-center space-x-2">
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Tail Lines:</label>
                <select wire:model.live="lines" class="flex-1 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-2 text-gray-900 dark:text-white">
                    <option value="50">Last 50 lines</option>
                    <option value="100">Last 100 lines</option>
                    <option value="250">Last 250 lines</option>
                    <option value="500">Last 500 lines</option>
                    <option value="1000">Last 1000 lines</option>
                </select>
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
                <span class="ml-2 font-mono text-gray-300">{{ $source }}.log</span>
            </div>
            <div class="font-mono text-gray-400 text-xs">
                ESC to clear • Filter: {{ $level === 'all' ? 'All' : $level }}
            </div>
        </div>

        <div class="p-4 overflow-x-auto max-h-[600px] overflow-y-auto space-y-1 font-mono text-xs leading-relaxed">
            @forelse ($logData['entries'] as $idx => $entry)
                <div class="flex items-start space-x-3 py-1 px-2 rounded hover:bg-gray-900/60 transition group">
                    <span class="text-gray-600 select-none w-8 text-right shrink-0">{{ $idx + 1 }}</span>
                    <span class="text-gray-500 shrink-0 text-xs">{{ $entry['timestamp'] }}</span>

                    @if ($entry['level'] === 'CRITICAL' || $entry['level'] === 'ERROR')
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800 shrink-0">
                            {{ $entry['level'] }}
                        </span>
                    @elseif ($entry['level'] === 'WARNING')
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800 shrink-0">
                            WARN
                        </span>
                    @elseif ($entry['level'] === 'NOTICE')
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-sky-950 text-sky-300 border border-sky-800 shrink-0">
                            NOTICE
                        </span>
                    @else
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-gray-800 text-gray-300 shrink-0">
                            INFO
                        </span>
                    @endif

                    @if ($entry['client_ip'])
                        <span class="text-indigo-400 shrink-0">{{ $entry['client_ip'] }}</span>
                    @endif

                    <span class="text-gray-200 break-all flex-1">{{ $entry['message'] }}</span>
                </div>
            @empty
                <div class="py-12 text-center text-gray-500">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p>No log entries found matching your search and severity filters.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
