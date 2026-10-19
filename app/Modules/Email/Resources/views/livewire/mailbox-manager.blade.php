<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Email Accounts & Mailboxes</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Virtual mailboxes, custom forwarders, Postfix/Dovecot storage limits, and automatic DKIM signing.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-button wire:click="openCreateForwarderModal" variant="secondary">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                New Forwarder
            </x-button>
            <x-button wire:click="openCreateMailboxModal" variant="primary">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Mailbox
            </x-button>
        </div>
    </div>

    <!-- Metrics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-metric-card 
            title="Total Mailboxes" 
            :value="$mailboxes->count()" 
            color="primary"
            subtitle="Configured inboxes"
        />
        <x-metric-card 
            title="Forwarders & Aliases" 
            :value="$aliases->count()" 
            color="indigo"
            subtitle="Active routing rules"
        />
        <x-metric-card 
            title="Allocated Quota" 
            :value="round($mailboxes->sum('quota_mb') / 1024, 1) . ' GB'" 
            color="amber"
            subtitle="Mail storage capacity"
        />
        <x-metric-card 
            title="DKIM Signing" 
            value="Enabled" 
            color="emerald"
            subtitle="RFC 6376 compliant"
        />
    </div>

    <!-- Account Switcher & Search Bar -->
    <x-card>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            @if($panel === 'admin')
                <div class="w-full sm:w-72">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Target Account</label>
                    <select 
                        wire:model.live="selectedAccountId" 
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm py-2 px-3 font-semibold focus:ring-2 focus:ring-primary-500"
                    >
                        @foreach($availableAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Tabs -->
            <div class="flex items-center gap-2 border-b sm:border-b-0 border-slate-200 dark:border-slate-800">
                <button 
                    wire:click="$set('activeTab', 'mailboxes')" 
                    class="px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'mailboxes' ? 'bg-primary-50 dark:bg-primary-950 text-primary-600 dark:text-primary-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    Mailboxes ({{ $mailboxes->count() }})
                </button>
                <button 
                    wire:click="$set('activeTab', 'forwarders')" 
                    class="px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'forwarders' ? 'bg-primary-50 dark:bg-primary-950 text-primary-600 dark:text-primary-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    Forwarders ({{ $aliases->count() }})
                </button>
            </div>

            <!-- Search -->
            <div class="relative flex-1 w-full sm:w-auto">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by email..." 
                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 text-sm"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </x-card>

    <!-- Main Content Area -->
    @if($activeTab === 'mailboxes')
        <x-card :noPadding="true">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-4">Email Address</th>
                            <th class="px-6 py-4">Storage Quota</th>
                            <th class="px-6 py-4">Usage</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($mailboxes as $mb)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                <!-- Email Address -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                            </svg>
                                        </div>
                                        <span class="font-mono font-semibold text-slate-900 dark:text-white select-all">
                                            {{ $mb->email }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Quota -->
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ $mb->quota_mb }} MB
                                </td>

                                <!-- Progress Usage -->
                                <td class="px-6 py-4">
                                    <div class="w-36 space-y-1">
                                        <div class="flex items-center justify-between text-[11px] font-mono text-slate-500">
                                            <span>{{ $mb->used_mb }} MB</span>
                                            <span>{{ $mb->usagePercent() }}%</span>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-primary-500 rounded-full" style="width: {{ $mb->usagePercent() }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <x-status-badge :status="$mb->status" />
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a 
                                            href="http://webmail.{{ $mb->mailDomain->name }}" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                            title="Launch Webmail"
                                        >
                                            Webmail
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>

                                        <button 
                                            wire:click="deleteMailbox({{ $mb->id }})" 
                                            wire:confirm="Are you sure you want to delete mailbox [{{ $mb->email }}] and all stored messages?"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                            title="Delete Mailbox"
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
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    No mailboxes found. Click "Create Mailbox" above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        <!-- Forwarders Tab -->
        <x-card :noPadding="true">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-4">Forwarding Source</th>
                            <th class="px-6 py-4">Destination Target</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($aliases as $alias)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4 font-mono font-semibold text-slate-900 dark:text-white select-all">
                                    {{ $alias->source_email }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-primary-600 dark:text-primary-400 select-all">
                                    &rarr; {{ $alias->destination_email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        wire:click="deleteForwarder({{ $alias->id }})" 
                                        wire:confirm="Are you sure you want to remove this email forwarder?"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                        title="Delete Forwarder"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    No email forwarders configured yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif

    <!-- Create Mailbox Modal -->
    @if($showCreateMailboxModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Create New Mailbox</h3>

                <form wire:submit="createMailbox" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Domain *</label>
                        <select wire:model="selectedDomainId" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                            @foreach($domains as $d)
                                <option value="{{ $d->id }}">{{ $d->domain }}</option>
                            @endforeach
                        </select>
                        @error('selectedDomainId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Username / Local Part *</label>
                        <div class="flex items-center gap-1.5">
                            <input 
                                type="text" 
                                wire:model="mailboxUser" 
                                placeholder="info" 
                                class="flex-1 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                            >
                            <span class="text-xs font-semibold text-slate-500">@domain</span>
                        </div>
                        @error('mailboxUser') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Password *</label>
                        <input 
                            type="password" 
                            wire:model="mailboxPassword" 
                            placeholder="Min 8 characters" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                        >
                        @error('mailboxPassword') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Storage Quota (MB) *</label>
                        <select wire:model="mailboxQuotaMb" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                            <option value="512">512 MB</option>
                            <option value="1024">1024 MB (1 GB - Default)</option>
                            <option value="2048">2048 MB (2 GB)</option>
                            <option value="5120">5120 MB (5 GB)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCreateMailboxModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Create Mailbox</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Create Forwarder Modal -->
    @if($showCreateForwarderModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Create Email Forwarder</h3>

                <form wire:submit="createForwarder" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Domain *</label>
                        <select wire:model="selectedDomainId" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                            @foreach($domains as $d)
                                <option value="{{ $d->id }}">{{ $d->domain }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Forwarding Address (Source) *</label>
                        <input 
                            type="text" 
                            wire:model="forwarderSource" 
                            placeholder="support" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                        >
                        @error('forwarderSource') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Forward To (Destination) *</label>
                        <input 
                            type="email" 
                            wire:model="forwarderDestination" 
                            placeholder="external@gmail.com" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                        >
                        @error('forwarderDestination') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCreateForwarderModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Create Forwarder</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
