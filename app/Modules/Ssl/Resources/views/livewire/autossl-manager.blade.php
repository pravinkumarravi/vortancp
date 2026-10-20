<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AutoSSL Fleet Manager</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Automated cluster-wide SSL scanner, automated Let's Encrypt issuance, and 30-day proactive renewal daemon.</p>
        </div>
        <button wire:click="runFleetAutoSsl" wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition disabled:opacity-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span wire:loading.remove wire:target="runFleetAutoSsl">Run AutoSSL for All Domains</span>
            <span wire:loading wire:target="runFleetAutoSsl">Scanning & Renewing Fleet...</span>
        </button>
    </div>

    <!-- Flash Alert -->
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

    <!-- Fleet Health Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Hosted Domains</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $overview['total_domains'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold">SSL Secured ({{ $overview['coverage_pct'] }}%)</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $overview['secured_domains'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">Expiring in &le; 30 Days</div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $overview['expiring_soon'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-rose-600 dark:text-rose-400 uppercase tracking-wider font-semibold">Unsecured (No SSL)</div>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $overview['unsecured_domains'] }}</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="relative w-full max-w-sm">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search domain name..."
                   class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg pl-9 pr-4 py-2 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div class="flex flex-wrap gap-1.5 text-xs">
            @foreach (['all' => 'All Domains', 'secured' => 'Secured', 'expiring' => 'Expiring Soon', 'unsecured' => 'Unsecured'] as $key => $label)
                <button wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-3 py-1.5 rounded-lg font-medium transition {{ $statusFilter === $key ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Domains AutoSSL Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                        <th class="py-3.5 px-4">Domain</th>
                        <th class="py-3.5 px-4">Account</th>
                        <th class="py-3.5 px-4">SSL Status</th>
                        <th class="py-3.5 px-4">Certificate Issuer</th>
                        <th class="py-3.5 px-4">Expires</th>
                        <th class="py-3.5 px-4 text-center">Auto-Renew</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($domains as $domain)
                        @php
                            $cert = $domain->activeSslCertificate;
                            $now = now();
                            $daysLeft = $cert ? (int) $now->diffInDays($cert->valid_until, false) : null;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                            <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">
                                {{ $domain->domain }}
                            </td>
                            <td class="py-3.5 px-4 text-xs font-mono text-gray-600 dark:text-gray-300">
                                {{ $domain->hostingAccount?->username ?? 'system' }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($cert && $daysLeft > 30)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                        <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Valid SSL
                                    </span>
                                @elseif ($cert && $daysLeft > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                                        Expiring ({{ $daysLeft }}d)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300">
                                        Unsecured
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-600 dark:text-gray-400">
                                {{ $cert ? $cert->issuer : 'None' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-500 dark:text-gray-400">
                                @if ($cert)
                                    <div>{{ $cert->valid_until->format('M d, Y') }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $daysLeft }} days remaining</div>
                                @else
                                    <span class="text-rose-500 font-medium">No Certificate</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if ($cert)
                                    <button wire:click="toggleAutoRenew({{ $cert->id }})"
                                            class="text-xs font-semibold px-2 py-1 rounded transition {{ $cert->auto_renew ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300' }}">
                                        {{ $cert->auto_renew ? 'Enabled' : 'Disabled' }}
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button wire:click="runDomainAutoSsl({{ $domain->id }})"
                                        class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-xs font-semibold rounded-md border border-indigo-200 dark:border-indigo-800 transition">
                                    Run AutoSSL
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                No domains found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
