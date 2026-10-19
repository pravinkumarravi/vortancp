<div>
    <!-- PAGE HEADER -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Reseller Resource Hub</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your customer accounts, allocated resource quotas, and hosting packages.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 text-xs font-semibold border border-blue-200 dark:border-blue-800">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <span>Active Reseller Pool</span>
            </span>
        </div>
    </div>

    <!-- POOL ALLOCATION CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        
        <x-metric-card 
            title="Managed Clients" 
            value="1 Client" 
            subtitle="Acme Web Client"
            change="Active"
            trend="up"
            gradient="from-blue-600 to-indigo-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Assigned Disk Quota" 
            value="18.5 GB" 
            subtitle="Limit: 100 GB Allocated"
            progress="18.5"
            gradient="from-emerald-600 to-teal-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Monthly Bandwidth" 
            value="42.8 GB" 
            subtitle="Limit: 1,000 GB Allocated"
            progress="4.28"
            gradient="from-cyan-600 to-blue-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card 
            title="Active Domains" 
            value="3 Domains" 
            subtitle="Limit: 25 Domains max"
            progress="12"
            gradient="from-amber-600 to-orange-600"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </x-slot:icon>
        </x-metric-card>

    </div>

    <!-- RESELLER QUICK ACTIONS -->
    <x-card title="Reseller Management Shortcuts" subtitle="Quick administrative workflows">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="#" class="p-4 rounded-xl border border-gray-100 dark:border-slate-800 hover:border-blue-500/50 bg-gray-50/50 dark:bg-slate-800/40 transition-colors block">
                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Create New Customer Account</h5>
                <p class="text-xs text-gray-500 mt-1">Onboard client, assign package, and set up isolated sandbox.</p>
            </a>
            <a href="#" class="p-4 rounded-xl border border-gray-100 dark:border-slate-800 hover:border-blue-500/50 bg-gray-50/50 dark:bg-slate-800/40 transition-colors block">
                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Custom Hosting Packages</h5>
                <p class="text-xs text-gray-500 mt-1">Configure service plans within your allocated resource pool.</p>
            </a>
            <a href="#" class="p-4 rounded-xl border border-gray-100 dark:border-slate-800 hover:border-blue-500/50 bg-gray-50/50 dark:bg-slate-800/40 transition-colors block">
                <h5 class="text-sm font-semibold text-gray-900 dark:text-white">White-Label Branding</h5>
                <p class="text-xs text-gray-500 mt-1">Customize control panel logo, name, and notification footers.</p>
            </a>
        </div>
    </x-card>
</div>
