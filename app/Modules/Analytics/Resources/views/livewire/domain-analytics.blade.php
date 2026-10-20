<div class="space-y-6">
    <!-- Header & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Traffic & Visitor Analytics
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Deep HTTP traffic inspection, unique visitor graphs, bandwidth consumption, and error rate monitoring.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($domains->isNotEmpty())
                <select wire:model.live="selectedDomainId" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs text-gray-900 dark:text-white font-mono">
                    @foreach($domains as $d)
                        <option value="{{ $d->id }}">{{ $d->domain }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="timeframe" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 text-xs text-gray-900 dark:text-white">
                <option value="7">Last 7 Days</option>
                <option value="14">Last 14 Days</option>
                <option value="30">Last 30 Days</option>
            </select>

            <button
                wire:click="generateSampleTraffic"
                type="button"
                class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-700 transition"
            >
                Refresh Data
            </button>
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
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Unique Visitors</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($analytics['unique_visitors']) }}</div>
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1">Unique IP fingerprints</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-semibold">Total HTTP Requests</div>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($analytics['total_requests']) }}</div>
            <div class="text-[11px] text-gray-500 mt-1">Nginx server hits</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Bandwidth Transferred</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1 font-mono">{{ $formattedBandwidth }}</div>
            <div class="text-[11px] text-gray-500 mt-1">Egress network traffic</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">Error Status (4xx/5xx)</div>
            <div class="text-2xl font-bold {{ ($statusPercent['4xx'] + $statusPercent['5xx']) > 5 ? 'text-rose-600' : 'text-emerald-600 dark:text-emerald-400' }} mt-1">
                {{ $statusPercent['4xx'] + $statusPercent['5xx'] }}%
            </div>
            <div class="text-[11px] text-gray-500 mt-1">{{ $analytics['status_4xx'] + $analytics['status_5xx'] }} total errors</div>
        </div>
    </div>

    <!-- Daily Visitor & Traffic Chart Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Daily Traffic Volume (Last {{ $timeframe }} Days)</h2>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
                    <span class="w-3 h-3 rounded bg-indigo-600 inline-block"></span>
                    HTTP Requests
                </span>
                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                    <span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span>
                    Unique Visitors
                </span>
            </div>
        </div>

        @php
            $maxReq = 1;
            foreach($analytics['daily_chart'] as $point) {
                if ($point['requests'] > $maxReq) { $maxReq = $point['requests']; }
            }
        @endphp

        <!-- Visual Bar Chart -->
        <div class="h-44 flex items-end gap-2 pt-6 pb-2 border-b border-gray-200 dark:border-gray-700">
            @forelse($analytics['daily_chart'] as $day)
                @php
                    $reqHeight = max(8, round(($day['requests'] / $maxReq) * 140));
                    $visHeight = max(4, round(($day['visitors'] / $maxReq) * 140));
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end group relative">
                    <!-- Tooltip -->
                    <div class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-950 text-white text-[10px] p-2 rounded shadow-xl whitespace-nowrap z-20 font-mono">
                        <div class="font-bold text-gray-300">{{ $day['date'] }}</div>
                        <div class="text-indigo-400">Hits: {{ number_format($day['requests']) }}</div>
                        <div class="text-emerald-400">Visitors: {{ number_format($day['visitors']) }}</div>
                        <div class="text-gray-400">{{ $day['bandwidth_mb'] }} MB</div>
                    </div>

                    <div class="w-full flex items-end justify-center gap-1 h-full">
                        <div class="w-1/2 bg-indigo-500 hover:bg-indigo-600 rounded-t transition-all" style="height: {{ $reqHeight }}px"></div>
                        <div class="w-1/2 bg-emerald-400 hover:bg-emerald-500 rounded-t transition-all" style="height: {{ $visHeight }}px"></div>
                    </div>
                    <span class="text-[10px] text-gray-400 truncate w-full text-center">{{ $day['label'] }}</span>
                </div>
            @empty
                <div class="w-full text-center text-xs text-gray-500 py-12">
                    No traffic activity recorded for this period.
                </div>
            @endforelse
        </div>
    </div>

    <!-- HTTP Status Code Distribution Bar -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-gray-900 dark:text-white">HTTP Response Code Breakdown</span>
            <span class="text-gray-500">{{ number_format($analytics['total_requests']) }} total inspected requests</span>
        </div>

        <div class="w-full bg-gray-200 dark:bg-gray-700 h-3 rounded-full overflow-hidden flex">
            <div class="bg-emerald-500 transition-all" style="width: {{ $statusPercent['2xx'] }}%" title="2xx Success: {{ $statusPercent['2xx'] }}%"></div>
            <div class="bg-blue-500 transition-all" style="width: {{ $statusPercent['3xx'] }}%" title="3xx Redirect: {{ $statusPercent['3xx'] }}%"></div>
            <div class="bg-amber-500 transition-all" style="width: {{ $statusPercent['4xx'] }}%" title="4xx Client Error: {{ $statusPercent['4xx'] }}%"></div>
            <div class="bg-rose-500 transition-all" style="width: {{ $statusPercent['5xx'] }}%" title="5xx Server Error: {{ $statusPercent['5xx'] }}%"></div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs pt-1">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-gray-600 dark:text-gray-400">2xx Success:</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ $statusPercent['2xx'] }}%</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <span class="text-gray-600 dark:text-gray-400">3xx Redirects:</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ $statusPercent['3xx'] }}%</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <span class="text-gray-600 dark:text-gray-400">4xx Not Found:</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ $statusPercent['4xx'] }}%</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                <span class="text-gray-600 dark:text-gray-400">5xx Server Errors:</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ $statusPercent['5xx'] }}%</span>
            </div>
        </div>
    </div>

    <!-- Top Paths & Referrers Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Top Requested URLs -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-xs space-y-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center justify-between">
                <span>Top Requested Paths</span>
                <span class="text-xs text-gray-500 font-normal">Hits</span>
            </h3>

            <div class="space-y-2 text-xs">
                @forelse($analytics['top_paths'] as $path => $count)
                    <div class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-900/60 font-mono">
                        <span class="text-indigo-600 dark:text-indigo-400 truncate max-w-xs">{{ $path }}</span>
                        <span class="font-bold text-gray-900 dark:text-white ml-2 shrink-0">{{ number_format($count) }}</span>
                    </div>
                @empty
                    <div class="text-gray-400 text-xs py-4 text-center">No path requests recorded yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Top Referrers -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-xs space-y-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center justify-between">
                <span>Top Referrer Domains</span>
                <span class="text-xs text-gray-500 font-normal">Visits</span>
            </h3>

            <div class="space-y-2 text-xs">
                @forelse($analytics['top_referrers'] as $ref => $count)
                    <div class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-900/60 font-mono">
                        <span class="text-gray-800 dark:text-gray-300 truncate max-w-xs">{{ $ref }}</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 ml-2 shrink-0">{{ number_format($count) }}</span>
                    </div>
                @empty
                    <div class="text-gray-400 text-xs py-4 text-center">Direct visits / no external referrers.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
