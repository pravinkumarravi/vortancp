<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Domains & Virtual Hosts</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Configure websites, subdomains, reverse proxies, and webserver configurations across clusters.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <x-button wire:click="openCreateModal" variant="primary">
                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Domain
            </x-button>
        </div>
    </div>

    <!-- Metrics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-metric-card 
            title="Total Domains" 
            :value="$domains->count()" 
            color="primary"
            subtitle="Configured vhosts"
        />
        <x-metric-card 
            title="Primary Sites" 
            :value="$domains->where('type', 'primary')->count()" 
            color="emerald"
            subtitle="Core root accounts"
        />
        <x-metric-card 
            title="Subdomains" 
            :value="$domains->where('type', 'subdomain')->count()" 
            color="amber"
            subtitle="App & API endpoints"
        />
        <x-metric-card 
            title="HTTPS Enforced" 
            :value="$domains->where('force_https', true)->count()" 
            color="emerald"
            subtitle="HSTS & TLS active"
        />
    </div>

    <!-- Filters & Search -->
    <x-card>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex-1 w-full sm:w-auto">
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search by domain name..." 
                        class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
                    >
                    <svg class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <!-- Type Filter -->
                <select 
                    wire:model.live="typeFilter" 
                    class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm py-2 px-3 focus:ring-2 focus:ring-primary-500"
                >
                    <option value="all">All Domain Types</option>
                    <option value="primary">Primary</option>
                    <option value="addon">Addon Domain</option>
                    <option value="subdomain">Subdomain</option>
                    <option value="alias">Alias / Parked</option>
                </select>

                <!-- Account Filter -->
                @if($panel === 'admin')
                    <select 
                        wire:model.live="accountFilter" 
                        class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm py-2 px-3 focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">All Accounts</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
    </x-card>

    <!-- Domains Table -->
    <x-card :noPadding="true">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Domain / Virtual Host</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Account Owner</th>
                        <th class="px-6 py-4">Document Root</th>
                        <th class="px-6 py-4">PHP Engine</th>
                        <th class="px-6 py-4">Security / TLS</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($domains as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <!-- Domain Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-xs uppercase">
                                        {{ substr($item->domain, 0, 2) }}
                                    </div>
                                    <div>
                                        <a href="http://{{ $item->domain }}" target="_blank" class="font-semibold text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 inline-flex items-center gap-1">
                                            {{ $item->domain }}
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                        @if($item->parentDomain)
                                            <p class="text-xs text-slate-400 mt-0.5">Parent: {{ $item->parentDomain->domain }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Type Badge -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                    @if($item->type === 'primary') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($item->type === 'addon') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300
                                    @elseif($item->type === 'subdomain') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                    @else bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 @endif">
                                    {{ ucfirst($item->type) }}
                                </span>
                            </td>

                            <!-- Account Owner -->
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $item->hostingAccount->username }}
                                </span>
                                <div class="text-xs text-slate-400">
                                    {{ $item->hostingAccount->customer?->name ?? 'Standard' }}
                                </div>
                            </td>

                            <!-- Document Root -->
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded select-all">
                                    {{ $item->document_root }}
                                </span>
                            </td>

                            <!-- PHP Engine -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 font-medium text-xs text-slate-700 dark:text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    PHP {{ $item->php_version }} FPM
                                </span>
                            </td>

                            <!-- Security / TLS -->
                            <td class="px-6 py-4">
                                <button 
                                    wire:click="toggleHttps({{ $item->id }})" 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition
                                    {{ $item->force_https ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}"
                                    title="Click to toggle Force HTTPS"
                                >
                                    <svg class="w-3.5 h-3.5 {{ $item->force_https ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ $item->force_https ? 'Force HTTPS' : 'HTTP/HTTPS' }}
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(!$item->isPrimary())
                                        <button 
                                            wire:click="triggerDelete({{ $item->id }})"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Delete Domain"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Root Domain</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <svg class="w-12 h-12 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    <p class="font-medium">No domains found matching the criteria.</p>
                                    <p class="text-xs text-slate-400">Click "Add Domain" above to register an addon domain, subdomain, or alias.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Create Domain Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-5 animate-in fade-in duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Add New Domain / Host</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Provision an isolated vhost with automatic webserver configuration</p>
                    </div>
                    <button wire:click="$set('showCreateModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="createDomain" class="space-y-4">
                    <!-- Hosting Account Select (if admin) -->
                    @if($panel === 'admin')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Hosting Account *</label>
                            <select wire:model="selectedAccountId" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5">
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                                @endforeach
                            </select>
                            @error('selectedAccountId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Domain Type -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Domain Type *</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs cursor-pointer transition
                                {{ $domainType === 'addon' ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/40 text-primary-600 font-semibold' : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}">
                                <input type="radio" wire:model.live="domainType" value="addon" class="sr-only">
                                <span>Addon Domain</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs cursor-pointer transition
                                {{ $domainType === 'subdomain' ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/40 text-primary-600 font-semibold' : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}">
                                <input type="radio" wire:model.live="domainType" value="subdomain" class="sr-only">
                                <span>Subdomain</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs cursor-pointer transition
                                {{ $domainType === 'alias' ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/40 text-primary-600 font-semibold' : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}">
                                <input type="radio" wire:model.live="domainType" value="alias" class="sr-only">
                                <span>Alias / Parked</span>
                            </label>
                        </div>
                    </div>

                    <!-- Domain Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            {{ $domainType === 'subdomain' ? 'Subdomain Prefix *' : 'Domain Name (FQDN) *' }}
                        </label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                wire:model="domainName" 
                                placeholder="{{ $domainType === 'subdomain' ? 'blog' : 'example.com' }}" 
                                class="flex-1 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5"
                            >
                            @if($domainType === 'subdomain')
                                <span class="text-sm font-semibold text-slate-500">.</span>
                                <select wire:model="parentDomainId" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5">
                                    <option value="">Select Root Domain</option>
                                    @foreach($parentDomains as $p)
                                        <option value="{{ $p->id }}">{{ $p->domain }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        @error('domainName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        @error('parentDomainId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- PHP Version -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">PHP Version *</label>
                            <select wire:model="phpVersion" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5">
                                <option value="8.4">PHP 8.4 (Default)</option>
                                <option value="8.3">PHP 8.3</option>
                                <option value="8.2">PHP 8.2</option>
                                <option value="8.1">PHP 8.1</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Custom Docroot (Optional)</label>
                            <input 
                                type="text" 
                                wire:model="customDocRoot" 
                                placeholder="Auto-generated if empty" 
                                class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono text-xs"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCreateModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Provision Domain</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center gap-3 text-rose-600 dark:text-rose-400">
                    <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Delete Domain?</h3>
                </div>

                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Are you sure you want to delete this domain? The virtual host configuration will be purged and web traffic will stop resolving immediately.
                </p>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <x-button type="button" wire:click="$set('showDeleteModal', false)" variant="secondary">Cancel</x-button>
                    <x-button type="button" wire:click="confirmDelete" variant="danger">Confirm Deletion</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
