<div>
    <!-- PAGE HEADER -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Infrastructure Overview</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Multi-node cluster telemetry, service health, and real-time operational status.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 text-xs font-semibold border border-purple-200 dark:border-purple-800">
                <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                <span>Super Administrator</span>
            </span>
        </div>
    </div>

    <!-- TELEMETRY METRIC CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        
        <x-metric-card 
            title="Managed Nodes" 
            value="1 Node" 
            subtitle="Local Master Node (Ubuntu 24.04)"
            change="100% Online"
            trend="up"
            gradient="from-purple-600 to-indigo-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Registered Users" 
            value="{{ $totalUsers }}" 
            subtitle="Admins, Resellers & Clients"
            change="+3 Local"
            trend="up"
            gradient="from-blue-600 to-cyan-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="System CPU Load" 
            value="{{ number_format($systemLoad, 2) }}" 
            subtitle="1-minute Load Average"
            progress="{{ min(100, $systemLoad * 25) }}"
            trend="neutral"
            gradient="from-emerald-600 to-teal-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Provisioning Engine" 
            value="Active" 
            subtitle="Queue: 0 pending tasks"
            change="Operational"
            trend="up"
            gradient="from-amber-600 to-orange-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </x-slot:icon>
        </x-metric-card>

    </div>

    <!-- MAIN TWO-COLUMN SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- SYSTEM DAEMON STATUS (2 COLUMNS) -->
        <div class="lg:col-span-2">
            <x-card title="System Daemon Health" subtitle="Current status of core infrastructure services">
                <div class="divide-y divide-gray-100 dark:divide-slate-800">
                    
                    <div class="py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center font-bold text-xs">NG</div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Nginx Web Server</h5>
                                <p class="text-xs text-gray-500">Reverse proxy & static asset accelerator</p>
                            </div>
                        </div>
                        <x-status-badge status="active" label="Running" />
                    </div>

                    <div class="py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 flex items-center justify-center font-bold text-xs">PHP</div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">PHP-FPM Multi-Version Pool</h5>
                                <p class="text-xs text-gray-500">Runtimes 8.1, 8.2, 8.3, 8.4 active</p>
                            </div>
                        </div>
                        <x-status-badge status="active" label="Running" />
                    </div>

                    <div class="py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center font-bold text-xs">DB</div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">MariaDB Database Engine</h5>
                                <p class="text-xs text-gray-500">Port 3306 & UNIX domain socket</p>
                            </div>
                        </div>
                        <x-status-badge status="active" label="Running" />
                    </div>

                    <div class="py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 flex items-center justify-center font-bold text-xs">DNS</div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Authoritative DNS (PowerDNS / BIND)</h5>
                                <p class="text-xs text-gray-500">DNSSEC engine enabled</p>
                            </div>
                        </div>
                        <x-status-badge status="active" label="Running" />
                    </div>

                    <div class="py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 flex items-center justify-center font-bold text-xs">SEC</div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Fail2ban Brute Force Shield</h5>
                                <p class="text-xs text-gray-500">Jails: sshd, nginx-req-limit, dovecot</p>
                            </div>
                        </div>
                        <x-status-badge status="active" label="Running" />
                    </div>

                </div>
            </x-card>
        </div>

        <!-- RECENT AUDIT TRAIL (1 COLUMN) -->
        <div>
            <x-card title="Recent Audit Activity" subtitle="Immutable operational log">
                <div class="space-y-4">
                    @forelse($recentAuditLogs as $log)
                        <div class="flex items-start gap-3 text-xs">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $log->event }}
                                </p>
                                <p class="text-gray-500 dark:text-gray-400 text-[11px]">
                                    by {{ $log->user?->name ?? 'System' }} &bull; {{ $log->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-4">No recent activity recorded.</p>
                    @endforelse
                </div>
            </x-card>
        </div>

    </div>
</div>
