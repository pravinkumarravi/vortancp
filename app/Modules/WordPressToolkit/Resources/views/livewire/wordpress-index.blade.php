<div class="space-y-6">
    <!-- Header with Breadcrumbs & Action -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </span>
                WordPress Toolkit
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Install, secure, manage, and sign in to WordPress instances with automated database provisioning and security hardening.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(count($accounts) > 0)
                <div class="flex items-center gap-2">
                    <label for="acc_select" class="text-xs font-semibold text-gray-500 uppercase">Account:</label>
                    <select id="acc_select" wire:model.live="selectedAccountId"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button wire:click="openInstallModal"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Install WordPress
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-200">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 dark:hover:text-rose-200">&times;</button>
        </div>
    @endif

    <!-- WordPress Instances Cards -->
    @if($instances->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-800/50">
            <div class="mx-auto w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">No WordPress Installations</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                There are no WordPress websites managed under this hosting account yet.
            </p>
            <div class="mt-6">
                <button wire:click="openInstallModal"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-all">
                    Install WordPress Now
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6">
            @foreach($instances as $instance)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden transition-all hover:shadow-md">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-sm">
                                    W
                                </div>
                                <div>
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $instance->site_title }}</h3>
                                        @if($instance->status === 'active')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                        @elseif($instance->status === 'maintenance')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Maintenance Mode
                                            </span>
                                        @endif
                                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono">
                                            WP {{ $instance->wp_version }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <a href="{{ $instance->site_url }}" target="_blank" class="hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1 font-medium">
                                            {{ $instance->site_url }}
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                        <span>&bull;</span>
                                        <span class="font-mono">{{ $instance->path }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button wire:click="ssoLogin({{ $instance->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    1-Click WP-Admin Login
                                </button>

                                <button wire:click="toggleMaintenance({{ $instance->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border {{ $instance->status === 'maintenance' ? 'border-amber-300 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    </svg>
                                    {{ $instance->status === 'maintenance' ? 'Disable Maintenance' : 'Maintenance Mode' }}
                                </button>

                                <button wire:click="confirmDelete({{ $instance->id }})"
                                    class="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Technical & Security Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 pt-2">
                            <!-- Database Info -->
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Database</span>
                                <div class="mt-1 text-sm font-mono font-medium text-gray-900 dark:text-gray-200 truncate">
                                    {{ $instance->db_name }}
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">User: {{ $instance->db_user }}</span>
                            </div>

                            <!-- Administrator Email -->
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Admin Email</span>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-200 truncate">
                                    {{ $instance->admin_email }}
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Prefix: {{ $instance->table_prefix }}</span>
                            </div>

                            <!-- Hardening: Block XML-RPC -->
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Block XML-RPC</span>
                                    <span class="text-xs text-gray-400">Mitigate brute-force & DDoS</span>
                                </div>
                                <button wire:click="toggleHardening({{ $instance->id }}, 'xmlrpc')"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $instance->xmlrpc_blocked ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out {{ $instance->xmlrpc_blocked ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </div>

                            <!-- Hardening: Disallow File Edit -->
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Disable File Edit</span>
                                    <span class="text-xs text-gray-400">Prevent theme/plugin editing</span>
                                </div>
                                <button wire:click="toggleHardening({{ $instance->id }}, 'file_editing')"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $instance->file_editing_disabled ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out {{ $instance->file_editing_disabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- 1-Click Install Modal -->
    @if($showInstallModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Install WordPress
                    </h3>
                    <button wire:click="$set('showInstallModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>

                <form wire:submit.prevent="installWordPress" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Target Domain</label>
                        <select wire:model="new_domain_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200">
                            @foreach($domains as $dom)
                                <option value="{{ $dom->id }}">{{ $dom->domain }}</option>
                            @endforeach
                        </select>
                        @error('new_domain_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Site Title</label>
                            <input type="text" wire:model="new_site_title"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200"
                                placeholder="My Blog">
                            @error('new_site_title') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Subdirectory (Optional)</label>
                            <input type="text" wire:model="new_sub_path"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200"
                                placeholder="e.g. blog or shop">
                            @error('new_sub_path') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Admin Email</label>
                            <input type="email" wire:model="new_admin_email"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200">
                            @error('new_admin_email') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">WP Version</label>
                            <input type="text" wire:model="new_wp_version"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200">
                            @error('new_wp_version') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="new_auto_update_core"
                                class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Enable automatic core updates</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showInstallModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            Provision & Install
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-md p-6">
                <div class="flex items-center gap-3 text-rose-600 dark:text-rose-400 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Delete WordPress Instance?</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Are you sure you want to detach and delete this WordPress instance? The record will be removed from your management console.
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button wire:click="deleteInstance"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm">
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
