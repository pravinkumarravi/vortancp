<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">PHP Multi-Version Manager</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Manage multiple side-by-side PHP engines, isolate per-user FPM pools, and customize execution limits.
            </p>
        </div>
    </div>

    <!-- Metrics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-metric-card 
            title="Installed Runtimes" 
            :value="count($runtimes)" 
            color="primary"
            subtitle="PHP 8.1 through 8.4"
        />
        <x-metric-card 
            title="Default Engine" 
            value="PHP 8.4" 
            color="emerald"
            subtitle="Current system default"
        />
        <x-metric-card 
            title="Active FPM Pools" 
            :value="$pools->count()" 
            color="indigo"
            subtitle="Isolated user process sandboxes"
        />
    </div>

    <!-- Account Switcher (if admin) -->
    @if($panel === 'admin')
        <x-card>
            <div class="w-full sm:w-80">
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
        </x-card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Domain PHP Versions Table -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Website Engine Assignments</h2>
            <x-card :noPadding="true">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Domain</th>
                                <th class="px-6 py-4">PHP Engine</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse($domains as $dom)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                                        {{ $dom->domain }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            PHP {{ $dom->php_version }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button 
                                            wire:click="openVersionModal({{ $dom->id }})" 
                                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                        >
                                            Change Version
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-500">
                                        No domains found for this account.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <!-- PHP-FPM Pools & Directives -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">PHP-FPM Process Pools</h2>
            <x-card :noPadding="true">
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($pools as $pool)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">PHP {{ $pool->version }} Pool</span>
                                    <span class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase">{{ $pool->pm_type }}</span>
                                </div>
                                <div class="text-xs font-mono text-slate-400 mt-1">
                                    Limit: <span class="text-slate-600 dark:text-slate-300 font-semibold">{{ $pool->memory_limit }}</span> &bull; 
                                    Max Exec: <span class="text-slate-600 dark:text-slate-300 font-semibold">{{ $pool->max_execution_time }}s</span> &bull; 
                                    Upload: <span class="text-slate-600 dark:text-slate-300 font-semibold">{{ $pool->upload_max_filesize }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button 
                                    wire:click="viewRawConfig({{ $pool->id }})" 
                                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                    title="View Raw Pool Configuration"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                </button>
                                <button 
                                    wire:click="openDirectivesModal({{ $pool->id }})" 
                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                >
                                    Edit Directives
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-500">
                            No process pools created yet.
                        </div>
                    @endforelse
                </div>
            </x-card>

            <!-- Installed Engines Summary -->
            <x-card>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Server Binary Inventory</h3>
                <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                    @foreach($runtimes as $rt)
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white">PHP {{ $rt['version'] }}</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $rt['status'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>

    <!-- Change Version Modal -->
    @if($showVersionModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Change PHP Engine</h3>

                <form wire:submit="saveVersion" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target PHP Version *</label>
                        <select wire:model="targetVersion" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono">
                            <option value="8.4">PHP 8.4 (Latest)</option>
                            <option value="8.3">PHP 8.3</option>
                            <option value="8.2">PHP 8.2</option>
                            <option value="8.1">PHP 8.1</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showVersionModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Switch Engine</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit Directives Modal -->
    @if($showDirectivesModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit PHP INI Directives</h3>

                <form wire:submit="saveDirectives" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Memory Limit *</label>
                            <select wire:model="memoryLimit" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                                <option value="128M">128 MB</option>
                                <option value="256M">256 MB (Standard)</option>
                                <option value="512M">512 MB (High Load)</option>
                                <option value="1024M">1024 MB (Enterprise)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Execution Time *</label>
                            <input 
                                type="number" 
                                wire:model="maxExecutionTime" 
                                class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Upload Max Filesize *</label>
                            <select wire:model="uploadMaxFilesize" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                                <option value="32M">32 MB</option>
                                <option value="64M">64 MB</option>
                                <option value="128M">128 MB</option>
                                <option value="256M">256 MB</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Post Max Size *</label>
                            <select wire:model="postMaxSize" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                                <option value="32M">32 MB</option>
                                <option value="64M">64 MB</option>
                                <option value="128M">128 MB</option>
                                <option value="256M">256 MB</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="displayErrors" wire:model="displayErrors" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        <label for="displayErrors" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                            Display errors (Development mode only)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showDirectivesModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Save Directives</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Raw FPM Config Modal -->
    @if($showRawModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Compiled PHP-FPM Pool Configuration</h3>
                    <button wire:click="$set('showRawModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <pre class="bg-slate-950 text-emerald-400 p-4 rounded-xl text-xs font-mono overflow-x-auto max-h-96 border border-slate-800 select-all leading-relaxed">{{ $rawPoolContent }}</pre>

                <div class="flex items-center justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
                    <x-button type="button" wire:click="$set('showRawModal', false)" variant="secondary">Close</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
