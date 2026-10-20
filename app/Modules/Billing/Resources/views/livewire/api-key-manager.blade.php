<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">API Keys & Billing Integration</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage secure API tokens for WHMCS, Blesta, HostBill, and external automation systems.</p>
        </div>
        <button wire:click="$set('showCreateModal', true)" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Generate New API Key
        </button>
    </div>

    <!-- Token Flash Banner -->
    @if ($plainTextToken)
        <div class="p-5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-emerald-800 dark:text-emerald-300 font-semibold">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>API Key Generated for [{{ $createdKeyName }}]</span>
                </div>
                <button wire:click="dismissToken" class="text-xs text-emerald-700 dark:text-emerald-400 hover:underline">Dismiss</button>
            </div>
            <p class="text-xs text-emerald-700 dark:text-emerald-400">
                Please copy your API token now. For your security, this key will <strong>never be shown again</strong>.
            </p>
            <div class="flex items-center space-x-2" x-data="{ copied: false }">
                <input type="text" readonly value="{{ $plainTextToken }}" class="flex-1 font-mono text-sm bg-white dark:bg-gray-900 border border-emerald-300 dark:border-emerald-700 rounded-lg px-3 py-2 text-emerald-950 dark:text-emerald-200 select-all focus:outline-none">
                <button @click="navigator.clipboard.writeText('{{ $plainTextToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition inline-flex items-center">
                    <span x-show="!copied">Copy Token</span>
                    <span x-show="copied" class="text-white">Copied!</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Active Tokens</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $keys->where('is_active', true)->count() }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Billing API Version</div>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">v1 REST</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-xs">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Supported Billing Systems</div>
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2">WHMCS • Blesta • HostBill • Custom cURL</div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="flex items-center justify-between">
        <div class="relative w-full max-w-sm">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search API keys by name or prefix..."
                   class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg pl-9 pr-4 py-2 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- API Keys Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                        <th class="py-3.5 px-4">Name</th>
                        <th class="py-3.5 px-4">Prefix</th>
                        <th class="py-3.5 px-4">Abilities</th>
                        <th class="py-3.5 px-4">Last Used</th>
                        <th class="py-3.5 px-4">Expires</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($keys as $key)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                            <td class="py-3.5 px-4 font-medium text-gray-900 dark:text-white">
                                {{ $key->name }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-0.5 rounded">
                                    {{ $key->key_prefix }}...
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if (in_array('*', $key->abilities ?? ['*'], true))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        All Capabilities (*)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ implode(', ', $key->abilities) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ $key->expires_at ? $key->expires_at->format('M d, Y') : 'Never' }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($key->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300">Disabled</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <button wire:click="toggleKeyStatus({{ $key->id }})" class="text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                    {{ $key->is_active ? 'Disable' : 'Enable' }}
                                </button>
                                <button wire:click="deleteKey({{ $key->id }})" wire:confirm="Are you sure you want to permanently revoke this API key?" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline">
                                    Revoke
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                No API keys found. Generate a key to connect external billing systems.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- WHMCS & Integration Guide Documentation Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-gray-900 dark:text-white">WHMCS & REST API Integration Guide</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Configure your WHMCS server module or automation scripts using the endpoints below. Include your token in the <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-xs">X-API-Key</code> request header.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Key Endpoints</div>
                <div class="mt-2 space-y-1 text-xs font-mono text-gray-800 dark:text-gray-300">
                    <p><span class="text-emerald-600 font-bold">POST</span> /api/v1/billing/accounts/create</p>
                    <p><span class="text-amber-600 font-bold">POST</span> /api/v1/billing/accounts/{user}/suspend</p>
                    <p><span class="text-blue-600 font-bold">POST</span> /api/v1/billing/accounts/{user}/unsuspend</p>
                    <p><span class="text-rose-600 font-bold">POST</span> /api/v1/billing/accounts/{user}/terminate</p>
                    <p><span class="text-purple-600 font-bold">POST</span> /api/v1/billing/accounts/{user}/change-package</p>
                    <p><span class="text-sky-600 font-bold">GET</span> /api/v1/billing/accounts/{user}/status</p>
                    <p><span class="text-sky-600 font-bold">GET</span> /api/v1/billing/plans</p>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Sample cURL Command</div>
                <pre class="mt-2 text-xs font-mono text-gray-800 dark:text-gray-300 overflow-x-auto whitespace-pre p-2 bg-gray-100 dark:bg-gray-800 rounded">
curl -X POST https://your-domain.com/api/v1/billing/accounts/create \
  -H "X-API-Key: vortan_your_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "domain": "customersite.com",
    "customer_email": "client@example.com",
    "plan_name": "Pro Business"
  }'</pre>
            </div>
        </div>
    </div>

    <!-- Create API Key Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Generate API Key</h3>

                <form wire:submit="generateKey" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Friendly Name</label>
                        <input type="text" wire:model="keyName" placeholder="e.g. WHMCS Production Cluster" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-gray-900 dark:text-white">
                        @error('keyName') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Permission Scope</label>
                        <select wire:model="selectedAbility" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-gray-900 dark:text-white">
                            <option value="*">Full Access (*)</option>
                            <option value="accounts.create">Account Provisioning Only</option>
                            <option value="accounts.suspend">Suspension & Lifecycle Only</option>
                            <option value="accounts.read">Read-only Telemetry & Status</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Expiration Date (Optional)</label>
                        <input type="date" wire:model="expiresAt" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-gray-900 dark:text-white">
                        @error('expiresAt') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                            Create Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
