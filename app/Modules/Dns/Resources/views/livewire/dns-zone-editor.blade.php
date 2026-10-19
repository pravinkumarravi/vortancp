<div class="space-y-6">
    <!-- Header & Zone Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">DNS Zone Editor</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Authoritative DNS management, resource record routing, and RFC 1035 zone file synthesis.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($currentZone)
                <x-button wire:click="viewRawZone" variant="secondary">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    Raw BIND Zone
                </x-button>
                <x-button wire:click="openAddModal" variant="primary">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Record
                </x-button>
            @endif
        </div>
    </div>

    <!-- Active Zone Banner & Metrics -->
    @if($currentZone)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-metric-card 
                title="Active Zone" 
                :value="$currentZone->name" 
                color="primary"
                :subtitle="'Primary NS: ' . $currentZone->primary_ns"
            />
            <x-metric-card 
                title="Zone Serial" 
                :value="$currentZone->serial" 
                color="indigo"
                subtitle="Auto-increments on change"
            />
            <x-metric-card 
                title="Total Records" 
                :value="$records->count()" 
                color="emerald"
                subtitle="Active resource records"
            />
            <x-metric-card 
                title="Default TTL" 
                :value="$currentZone->minimum_ttl . 's'" 
                color="amber"
                subtitle="Propagation caching rule"
            />
        </div>
    @endif

    <!-- Zone Switcher & Filters -->
    <x-card>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Zone Switcher -->
            <div class="w-full sm:w-72">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Select Domain Zone</label>
                <select 
                    wire:model.live="selectedZoneId" 
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm py-2 px-3 focus:ring-2 focus:ring-primary-500 font-semibold"
                >
                    @forelse($availableZones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @empty
                        <option value="">No DNS zones available</option>
                    @endforelse
                </select>
            </div>

            <!-- Search & Type Filter -->
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search name or IP..." 
                        class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 text-sm"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <select 
                    wire:model.live="typeFilter" 
                    class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm py-2 px-3 focus:ring-2 focus:ring-primary-500"
                >
                    <option value="all">All Types</option>
                    <option value="A">A (IPv4)</option>
                    <option value="AAAA">AAAA (IPv6)</option>
                    <option value="CNAME">CNAME (Alias)</option>
                    <option value="MX">MX (Mail)</option>
                    <option value="TXT">TXT (Text/SPF)</option>
                    <option value="NS">NS (Name Server)</option>
                    <option value="SRV">SRV (Service)</option>
                    <option value="CAA">CAA (Certificate)</option>
                </select>
            </div>
        </div>
    </x-card>

    <!-- Records Table -->
    @if($currentZone)
        <x-card :noPadding="true">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-4">Record Name</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Priority</th>
                            <th class="px-6 py-4">Target / Content</th>
                            <th class="px-6 py-4">TTL</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($records as $record)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                <!-- Record Name -->
                                <td class="px-6 py-4">
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white">
                                        {{ $record->name }}
                                    </span>
                                    <span class="text-xs text-slate-400">.{{ $currentZone->name }}</span>
                                </td>

                                <!-- Type -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold
                                        @if($record->type === 'A' || $record->type === 'AAAA') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                                        @elseif($record->type === 'CNAME') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                                        @elseif($record->type === 'MX') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                        @elseif($record->type === 'TXT') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                        @elseif($record->type === 'NS') bg-cyan-100 text-cyan-800 dark:bg-cyan-950/60 dark:text-cyan-300
                                        @else bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 @endif">
                                        {{ $record->type }}
                                    </span>
                                </td>

                                <!-- Priority -->
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ $record->priority !== null ? $record->priority : '-' }}
                                </td>

                                <!-- Content -->
                                <td class="px-6 py-4 font-mono text-xs break-all max-w-md">
                                    <span class="bg-slate-100 dark:bg-slate-800/80 px-2 py-1 rounded text-slate-800 dark:text-slate-200 select-all">
                                        {{ $record->content }}
                                    </span>
                                </td>

                                <!-- TTL -->
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                    {{ $record->ttl }}s
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            wire:click="openEditModal({{ $record->id }})"
                                            class="p-1.5 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Edit Record"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="deleteRecord({{ $record->id }})"
                                            wire:confirm="Are you sure you want to delete this DNS record?"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Delete Record"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    No records found matching query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        <x-card>
            <div class="py-12 text-center text-slate-500">
                <p>No active DNS zone available. Please register a domain first.</p>
            </div>
        </x-card>
    @endif

    <!-- Add / Edit Record Modal -->
    @if($showRecordModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-5 animate-in fade-in duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ $editingRecordId ? 'Edit DNS Record' : 'Add New DNS Record' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Authoritative DNS record for zone {{ $currentZone?->name }}</p>
                    </div>
                    <button wire:click="$set('showRecordModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveRecord" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Record Type -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Record Type *</label>
                            <select wire:model.live="recordType" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono">
                                <option value="A">A (Address)</option>
                                <option value="AAAA">AAAA (IPv6 Address)</option>
                                <option value="CNAME">CNAME (Alias)</option>
                                <option value="MX">MX (Mail Exchanger)</option>
                                <option value="TXT">TXT (Text)</option>
                                <option value="NS">NS (Name Server)</option>
                                <option value="SRV">SRV (Service)</option>
                                <option value="CAA">CAA (Certificate Authority)</option>
                            </select>
                        </div>

                        <!-- Record Name -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Name / Host *</label>
                            <input 
                                type="text" 
                                wire:model="recordName" 
                                placeholder="@ or subdomain" 
                                class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono"
                            >
                            @error('recordName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Priority (if MX or SRV) -->
                    @if($recordType === 'MX' || $recordType === 'SRV')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Priority *</label>
                            <input 
                                type="number" 
                                wire:model="recordPriority" 
                                placeholder="10" 
                                class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono"
                            >
                            @error('recordPriority') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Record Content -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target / Value / IP Address *</label>
                        <textarea 
                            wire:model="recordContent" 
                            rows="2" 
                            placeholder="{{ $recordType === 'A' ? '192.168.1.1' : ($recordType === 'CNAME' ? 'target.example.com.' : 'Content string') }}" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono"
                        ></textarea>
                        @error('recordContent') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- TTL -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">TTL (Time to Live) *</label>
                        <select wire:model="recordTtl" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono">
                            <option value="300">300 seconds (5 mins - testing)</option>
                            <option value="600">600 seconds (10 mins)</option>
                            <option value="1800">1800 seconds (30 mins)</option>
                            <option value="3600">3600 seconds (1 hour - standard)</option>
                            <option value="14400">14400 seconds (4 hours)</option>
                            <option value="86400">86400 seconds (24 hours)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showRecordModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Save Record</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Raw BIND Zone Modal -->
    @if($showRawZoneModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Authoritative BIND RFC 1035 Zone File</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Compiled output for zone {{ $currentZone?->name }}</p>
                    </div>
                    <button wire:click="$set('showRawZoneModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="relative">
                    <pre class="bg-slate-950 text-emerald-400 p-4 rounded-xl text-xs font-mono overflow-x-auto max-h-96 border border-slate-800 select-all leading-relaxed">{{ $rawZoneContent }}</pre>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <x-button type="button" wire:click="$set('showRawZoneModal', false)" variant="secondary">Close</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
