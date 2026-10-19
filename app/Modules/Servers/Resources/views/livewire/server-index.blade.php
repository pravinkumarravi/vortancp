<div>
    <!-- PAGE HEADER -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Server Nodes & Infrastructure</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage physical, virtual, and cloud nodes running the Vortan Server Agent.</p>
        </div>
        <div>
            <x-button wire:click="$set('showRegisterModal', true)" variant="primary">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Connect Server Node</span>
            </x-button>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="w-full sm:w-80">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Filter by name, hostname, or IP..."
                class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
            >
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="statusFilter" class="px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-xs font-semibold">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="offline">Offline</option>
            </select>
        </div>
    </div>

    <!-- SERVERS GRID / TABLE -->
    <div class="grid grid-cols-1 gap-5">
        @forelse($servers as $server)
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    
                    <!-- LEFT NODE IDENTIFIER -->
                    <div class="flex items-start gap-4 min-w-[280px]">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-purple-500/10">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $server->name }}</h3>
                                <x-status-badge :status="$server->status" />
                            </div>
                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">{{ $server->hostname }} &bull; {{ $server->ip_address }}</p>
                            <div class="flex items-center gap-2 mt-2 text-[11px] text-gray-500">
                                <span>{{ $server->os_version }}</span>
                                <span>&bull;</span>
                                <span>{{ $server->cpu_cores }} vCPUs</span>
                                <span>&bull;</span>
                                <span class="capitalize">{{ str_replace('_', ' ', $server->connection_type) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- TELEMETRY BARS -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 flex-1 max-w-2xl">
                        
                        <!-- LOAD -->
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-gray-500">CPU Load</span>
                                <span class="text-gray-900 dark:text-white font-mono">{{ number_format($server->load_average, 2) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min(100, $server->load_average * 25) }}%"></div>
                            </div>
                        </div>

                        <!-- RAM -->
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-gray-500">Memory</span>
                                <span class="text-gray-900 dark:text-white font-mono">{{ $server->getRamUsagePercentage() }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $server->getRamUsagePercentage() }}%"></div>
                            </div>
                        </div>

                        <!-- DISK -->
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-gray-500">Disk Storage</span>
                                <span class="text-gray-900 dark:text-white font-mono">{{ $server->getDiskUsagePercentage() }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $server->getDiskUsagePercentage() }}%"></div>
                            </div>
                        </div>

                    </div>

                    <!-- ACTIONS -->
                    <div class="flex items-center gap-2 shrink-0">
                        <x-button 
                            wire:click="syncHealth({{ $server->id }})" 
                            variant="secondary" 
                            size="sm"
                            target="syncHealth({{ $server->id }})"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            <span>Sync Telemetry</span>
                        </x-button>

                        <x-button 
                            wire:click="toggleMaintenance({{ $server->id }})" 
                            variant="outline" 
                            size="sm"
                            target="toggleMaintenance({{ $server->id }})"
                        >
                            <span>{{ $server->status === 'maintenance' ? 'Activate' : 'Maintenance' }}</span>
                        </x-button>
                    </div>

                </div>

                <!-- CAPABILITIES CHIPS -->
                @if($server->capabilities->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-800/80 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Daemons:</span>
                        @foreach($server->capabilities as $cap)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-slate-700 font-mono">
                                {{ $cap->capability }}@if($cap->version)<span class="text-gray-400 ml-1">v{{ $cap->version }}</span>@endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl">
                <p class="text-sm text-gray-500">No server nodes found matching your filter criteria.</p>
            </div>
        @endforelse
    </div>

    <!-- REGISTER NODE MODAL -->
    @if($showRegisterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Connect New Server Node</h3>
                    <button wire:click="$set('showRegisterModal', false)" class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="registerNode" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Node Display Name</label>
                        <input type="text" wire:model="nodeName" placeholder="e.g. EU Worker Node 02" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                        @error('nodeName') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Hostname</label>
                            <input type="text" wire:model="nodeHostname" placeholder="node02.vortan.net" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                            @error('nodeHostname') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Primary IP Address</label>
                            <input type="text" wire:model="nodeIp" placeholder="192.0.2.1" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                            @error('nodeIp') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Agent Port</label>
                            <input type="number" wire:model="nodePort" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                            @error('nodePort') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Connection Protocol</label>
                            <select wire:model="nodeConnectionType" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                                <option value="local_socket">Local UNIX Socket</option>
                                <option value="remote_mtls">Remote Mutual TLS</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Agent Authentication Token</label>
                        <input type="password" wire:model="nodeAuthToken" placeholder="vortan_sec_..." class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                        <p class="text-[11px] text-gray-500 mt-1">Shared secret generated during `vortan-agent install` on the target node.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showRegisterModal', false)" variant="outline">
                            Cancel
                        </x-button>
                        <x-button type="submit" variant="primary">
                            Verify & Register Node
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
