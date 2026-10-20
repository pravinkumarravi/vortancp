<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                DNS Cluster & Nameserver Fleet
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Automated master-slave zone synchronization across external BIND9/PowerDNS nameserver nodes with live latency tracking.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                wire:click="pingAll"
                type="button"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-700 shadow-sm text-xs font-semibold rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Ping Fleet
            </button>
            <button
                wire:click="syncAll"
                type="button"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-700 shadow-sm text-xs font-semibold rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-4 h-4 mr-1.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync All Zones
            </button>
            <button
                wire:click="$set('showAddModal', true)"
                type="button"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Nameserver Node
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
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Total Nameservers</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $metrics['total_nodes'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold">Operational (Online)</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $metrics['active_nodes'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-semibold">Authoritative Zones</div>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $metrics['total_zones'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">Average Latency</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1 font-mono">{{ $metrics['avg_latency'] }} ms</div>
        </div>
    </div>

    <!-- Cluster Nodes Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-xs">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Active Cluster Nameserver Nodes</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 uppercase font-semibold border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Nameserver</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Sync Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Synced Zones</th>
                        <th class="px-4 py-3">Latency</th>
                        <th class="px-4 py-3">Last Synced</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($nodes as $node)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-900 dark:text-white">{{ $node->name }}</div>
                                <div class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400">{{ $node->hostname }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">
                                {{ $node->ip_address }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $node->sync_type === 'master' ? 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300' : 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300' }}">
                                    {{ $node->sync_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($node->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-400 border border-rose-300 dark:border-rose-800">
                                        Offline
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white font-mono">
                                {{ $node->zones_count }} zones
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">
                                {{ $node->latency_ms }} ms
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-[11px]">
                                {{ $node->last_synced_at ? $node->last_synced_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="px-4 py-3 text-right space-x-1">
                                <button
                                    wire:click="pingNode({{ $node->id }})"
                                    type="button"
                                    class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
                                >
                                    Ping
                                </button>
                                <button
                                    wire:click="syncNode({{ $node->id }})"
                                    type="button"
                                    class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded transition"
                                >
                                    Sync
                                </button>
                                <button
                                    wire:click="deleteNode({{ $node->id }})"
                                    wire:confirm="Remove this nameserver node from cluster synchronization?"
                                    type="button"
                                    class="px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded transition"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No external cluster nodes configured. Click "Add Nameserver Node" to set up high-availability DNS synchronization.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent DNS Sync Logs -->
    @if($recentLogs->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Live Zone Sync Stream</h3>
            <div class="space-y-2">
                @foreach($recentLogs as $log)
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-800 text-xs font-mono">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $log->node->hostname ?? 'Node' }}</span>
                            <span class="text-gray-500">&bull;</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $log->details }}</span>
                        </div>
                        <span class="text-gray-500 text-[11px]">{{ $log->created_at?->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Add Node Modal -->
    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-lg w-full overflow-hidden">
                <form wire:submit.prevent="addNode">
                    <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Register DNS Cluster Nameserver</h3>
                        <button type="button" wire:click="$set('showAddModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Friendly Label</label>
                            <input type="text" wire:model="name" placeholder="e.g. US-East Primary NS" class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white" />
                            @error('name') <span class="text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nameserver Hostname (FQDN)</label>
                            <input type="text" wire:model="hostname" placeholder="e.g. ns1.vortancp.net" class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white font-mono" />
                            @error('hostname') <span class="text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">IP Address</label>
                                <input type="text" wire:model="ip_address" placeholder="198.51.100.5" class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white font-mono" />
                                @error('ip_address') <span class="text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Sync Role</label>
                                <select wire:model="sync_type" class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white">
                                    <option value="slave">Slave (Push zones to node)</option>
                                    <option value="master">Master (Bi-directional)</option>
                                    <option value="write_only">Write Only</option>
                                    <option value="standalone">Standalone (Manual)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Remote Vortan Agent API Key</label>
                            <input type="password" wire:model="api_key" placeholder="••••••••••••••••••••••••••••••••" class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white font-mono" />
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showAddModal', false)" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                            Register & Connect Node
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
