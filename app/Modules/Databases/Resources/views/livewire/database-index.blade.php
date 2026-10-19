<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Databases & Users</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Manage relational databases, database users, remote hosts, and granular privilege grants.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-button wire:click="openCreateUserModal" variant="secondary">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Create DB User
            </x-button>
            <x-button wire:click="openCreateDbModal" variant="primary">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
                Create Database
            </x-button>
        </div>
    </div>

    <!-- Account Switcher & Metrics -->
    @if($panel === 'admin')
        <x-card>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
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

                <div class="relative flex-1 w-full sm:w-auto">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search databases..." 
                        class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 text-sm"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </x-card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Databases Column (2 spans) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Active Databases ({{ $databases->count() }})</h2>
                @if($databases->isNotEmpty() && $dbUsers->isNotEmpty())
                    <button wire:click="openAssignModal" class="text-xs text-primary-600 dark:text-primary-400 font-semibold hover:underline">
                        + Assign User to Database
                    </button>
                @endif
            </div>

            <x-card :noPadding="true">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Database Name</th>
                                <th class="px-6 py-4">Engine</th>
                                <th class="px-6 py-4">Privileged Users</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse($databases as $db)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                    <!-- Name -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="font-mono font-semibold text-slate-900 dark:text-white select-all">{{ $db->name }}</span>
                                                <div class="text-xs text-slate-400">{{ $db->charset }} ({{ $db->collation }})</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Engine -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold uppercase bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                            {{ $db->db_engine }}
                                        </span>
                                    </td>

                                    <!-- Assigned Users -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse($db->users as $u)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                    {{ $u->username }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-slate-400 italic">No users assigned</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button 
                                                wire:click="openAssignModal({{ $db->id }})" 
                                                class="p-1.5 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                                title="Assign User"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="deleteDatabase({{ $db->id }})" 
                                                wire:confirm="Are you sure you want to delete database [{{ $db->name }}]? All tables and data will be permanently dropped."
                                                class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                                title="Delete Database"
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
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                        No databases found. Click "Create Database" above.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <!-- Database Users Column (1 span) -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Database Users ({{ $dbUsers->count() }})</h2>

            <x-card :noPadding="true">
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($dbUsers as $userItem)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <span class="font-mono text-xs font-bold text-slate-900 dark:text-white select-all">
                                    {{ $userItem->username }}
                                </span>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    Host: <span class="font-mono text-slate-600 dark:text-slate-300">{{ $userItem->host }}</span>
                                </div>
                            </div>
                            <button 
                                wire:click="deleteDatabaseUser({{ $userItem->id }})" 
                                wire:confirm="Are you sure you want to delete user [{{ $userItem->username }}]?"
                                class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                title="Delete User"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-500">
                            No database users created yet.
                        </div>
                    @endforelse
                </div>
            </x-card>

            <!-- Quick Connection Info -->
            <x-card>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Connection Settings</h3>
                <div class="space-y-1.5 text-xs font-mono text-slate-600 dark:text-slate-400">
                    <div><span class="text-slate-400">Host:</span> localhost</div>
                    <div><span class="text-slate-400">Port:</span> 3306</div>
                    <div><span class="text-slate-400">Socket:</span> /run/mysqld/mysqld.sock</div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Create Database Modal -->
    @if($showCreateDbModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Create New Database</h3>

                <form wire:submit="createDatabase" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Database Name *</label>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-2 rounded-lg text-slate-600 dark:text-slate-400">
                                {{ $currentAccount?->username }}_
                            </span>
                            <input 
                                type="text" 
                                wire:model="dbSuffix" 
                                placeholder="app" 
                                class="flex-1 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                            >
                        </div>
                        @error('dbSuffix') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Database Engine *</label>
                        <select wire:model="dbEngine" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                            <option value="mysql">MySQL 8.0 / 8.4</option>
                            <option value="mariadb">MariaDB 11</option>
                            <option value="postgresql">PostgreSQL 16</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCreateDbModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Create Database</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Create Database User Modal -->
    @if($showCreateUserModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Create Database User</h3>

                <form wire:submit="createDatabaseUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Username *</label>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-2 rounded-lg text-slate-600 dark:text-slate-400">
                                {{ $currentAccount?->username }}_
                            </span>
                            <input 
                                type="text" 
                                wire:model="userSuffix" 
                                placeholder="dbuser" 
                                class="flex-1 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                            >
                        </div>
                        @error('userSuffix') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Password *</label>
                        <input 
                            type="password" 
                            wire:model="userPassword" 
                            placeholder="Min 8 characters" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                        >
                        @error('userPassword') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Allowed Host *</label>
                        <input 
                            type="text" 
                            wire:model="userHost" 
                            placeholder="localhost or %" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono"
                        >
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCreateUserModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Create User</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Assign User to Database Modal -->
    @if($showAssignModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Assign User to Database</h3>

                <form wire:submit="assignUser" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Database *</label>
                            <select wire:model="assignDbId" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                                @foreach($databases as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">User *</label>
                            <select wire:model="assignUserId" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2 font-mono">
                                @foreach($dbUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->username }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Privileges</label>
                        <div class="grid grid-cols-3 gap-1.5 p-3 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 max-h-48 overflow-y-auto text-xs font-mono">
                            @foreach($allPrivileges as $priv)
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" wire:model="selectedPrivileges" value="{{ $priv }}" class="rounded text-primary-600 focus:ring-primary-500">
                                    <span>{{ $priv }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showAssignModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Grant Privileges</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
