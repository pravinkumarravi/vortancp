<div>
    <!-- PAGE HEADER -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hosting Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your websites, domains, databases, emails, and files.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-semibold border border-emerald-200 dark:border-emerald-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Subscription: Pro Developer Plan</span>
            </span>
        </div>
    </div>

    <!-- RESOURCE QUOTA CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        
        <x-metric-card 
            title="Hosted Domains" 
            value="1 Active" 
            subtitle="Limit: 5 Domains (4 Available)"
            progress="20"
            gradient="from-emerald-600 to-teal-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Disk Storage" 
            value="1.24 GB" 
            subtitle="Limit: 10.00 GB (88% Free)"
            progress="12.4"
            gradient="from-blue-600 to-indigo-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Databases" 
            value="2 / 10" 
            subtitle="MariaDB 10.11 Engine"
            progress="20"
            gradient="from-purple-600 to-fuchsia-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Mailboxes" 
            value="3 / 25" 
            subtitle="SPF, DKIM, DMARC Active"
            progress="12"
            gradient="from-amber-600 to-orange-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            </x-slot:icon>
        </x-metric-card>

    </div>

    <!-- QUICK ACTION WORKFLOWS -->
    <div class="mb-8">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Quick Hosting Tools</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
            
            <a href="#" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-500/50 hover:shadow-md transition-all text-center group">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                </div>
                <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">File Manager</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-500/50 hover:shadow-md transition-all text-center group">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 mx-auto flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" /></svg>
                </div>
                <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Databases</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-500/50 hover:shadow-md transition-all text-center group">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                </div>
                <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Email Accounts</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-500/50 hover:shadow-md transition-all text-center group">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">SSL / TLS</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-500/50 hover:shadow-md transition-all text-center group">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 dark:text-cyan-400 mx-auto flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                </div>
                <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">DNS Zones</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-500/50 hover:shadow-md transition-all text-center group">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 mx-auto flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                </div>
                <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Backups</span>
            </a>

        </div>
    </div>
</div>
