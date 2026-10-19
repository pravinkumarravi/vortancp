<div>
    <!-- PAGE HEADER -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hosting Accounts & Sandboxes</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage isolated tenant user environments, assigned quotas, and primary domain bindings.</p>
        </div>
        <div>
            <x-button wire:click="$set('showCreateModal', true)" variant="primary">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Create Hosting Account</span>
            </x-button>
        </div>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="w-full sm:w-80">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search by username or domain..."
                class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
            >
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="statusFilter" class="px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-xs font-semibold">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
    </div>

    <!-- ACCOUNTS TABLE -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-slate-950/60 text-xs uppercase font-semibold text-gray-500 border-b border-gray-100 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Account & Primary Domain</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Node Server</th>
                        <th class="px-6 py-4">Package</th>
                        <th class="px-6 py-4">Disk Usage</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($account->username, 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-900 dark:text-white block">{{ $account->primary_domain }}</span>
                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">user: {{ $account->username }} &bull; uid: {{ $account->uid }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900 dark:text-white block">{{ $account->customer->name }}</span>
                                <span class="text-xs text-gray-400">{{ $account->customer->email }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900 dark:text-white block">{{ $account->server->name }}</span>
                                <span class="text-xs font-mono text-gray-400">{{ $account->server->ip_address }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                    {{ $account->subscription?->hostingPlan?->name ?? 'Default' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs">{{ $account->disk_used_mb }} MB</span>
                            </td>
                            <td class="px-6 py-4">
                                <x-status-badge :status="$account->status" />
                                @if($account->status === 'suspended' && $account->suspended_reason)
                                    <span class="block text-[10px] text-rose-500 mt-0.5">{{ $account->suspended_reason }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-button 
                                    wire:click="triggerSuspend({{ $account->id }})" 
                                    variant="{{ $account->status === 'active' ? 'outline' : 'secondary' }}" 
                                    size="sm"
                                >
                                    <span>{{ $account->status === 'active' ? 'Suspend' : 'Unsuspend' }}</span>
                                </x-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No hosting accounts registered.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- CREATE ACCOUNT MODAL -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Provision New Hosting Sandbox</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="createAccount" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Customer / Client</label>
                        <select wire:model="selectedCustomerId" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                            <option value="">Select customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                            @endforeach
                        </select>
                        @error('selectedCustomerId') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Hosting Package</label>
                            <select wire:model="selectedPlanId" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                                <option value="">Select plan...</option>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedPlanId') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Server Placement Node</label>
                            <select wire:model="selectedServerId" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                                <option value="">Select server...</option>
                                @foreach($servers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->ip_address }})</option>
                                @endforeach
                            </select>
                            @error('selectedServerId') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Primary Domain (FQDN)</label>
                        <input type="text" wire:model.live.debounce.300ms="domain" placeholder="example.com" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                        @error('domain') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">System Username</label>
                        <input type="text" wire:model="username" placeholder="example" class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm font-mono">
                        <p class="text-[11px] text-gray-500 mt-1">Linux system user. Will own /home/{username}.</p>
                        @error('username') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCreateModal', false)" variant="outline">
                            Cancel
                        </x-button>
                        <x-button type="submit" variant="primary">
                            Provision Account
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- SUSPEND CONFIRMATION MODAL -->
    @if($showSuspendModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Change Account Status</h3>
                <p class="text-xs text-gray-500 mb-4">Provide an administrative reason if suspending the account.</p>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Reason (Optional)</label>
                    <input type="text" wire:model="suspendReason" placeholder="Non-payment, Terms breach, etc." class="w-full px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-sm">
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-slate-800">
                    <x-button type="button" wire:click="$set('showSuspendModal', false)" variant="outline">
                        Cancel
                    </x-button>
                    <x-button type="button" wire:click="toggleAccountStatus" variant="danger">
                        Confirm Status Toggle
                    </x-button>
                </div>
            </div>
        </div>
    @endif
</div>
