<div class="space-y-6">
    <!-- Header & Account Switcher -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <span class="p-2 rounded-lg bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </span>
                1-Click Application Installer
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Install, configure, and manage production web applications, frameworks, and CMS engines in seconds.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(count($accounts) > 0)
                <div class="flex items-center gap-2">
                    <label for="acc_select" class="text-xs font-semibold text-gray-500 uppercase">Account:</label>
                    <select id="acc_select" wire:model.live="selectedAccountId"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm py-2 px-3 focus:ring-sky-500 focus:border-sky-500 dark:text-gray-200">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-200">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 dark:hover:text-rose-200">&times;</button>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
        <div class="flex space-x-6">
            <button wire:click="$set('activeTab', 'catalog')"
                class="py-3 px-1 border-b-2 text-sm font-medium transition-all {{ $activeTab === 'catalog' ? 'border-sky-500 text-sky-600 dark:text-sky-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Application Catalog
            </button>
            <button wire:click="$set('activeTab', 'installed')"
                class="py-3 px-1 border-b-2 text-sm font-medium transition-all {{ $activeTab === 'installed' ? 'border-sky-500 text-sky-600 dark:text-sky-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Installed Applications ({{ count($installedApps) }})
            </button>
        </div>

        @if($activeTab === 'catalog')
            <div class="flex items-center gap-3 pb-2">
                <input type="text" wire:model.live.debounce.250ms="search" placeholder="Search apps..."
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-xs py-1.5 px-3 focus:ring-sky-500 focus:border-sky-500 dark:text-gray-200 w-48">
            </div>
        @endif
    </div>

    <!-- CATALOG VIEW -->
    @if($activeTab === 'catalog')
        <!-- Category Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            @foreach($categories as $cat)
                <button wire:click="$set('selectedCategory', '{{ $cat }}')"
                    class="px-3 py-1 rounded-full text-xs font-medium transition-all {{ $selectedCategory === $cat ? 'bg-sky-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $cat }}
                </button>
            @endforeach
        </div>

        <!-- Catalog Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($packages as $pkg)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 flex flex-col justify-between hover:shadow-md transition-all">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                {{ $pkg['category'] }}
                            </span>
                            <span class="text-xs font-mono text-gray-500">v{{ $pkg['version'] }}</span>
                        </div>

                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-lg">
                                {{ substr($pkg['name'], 0, 1) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $pkg['name'] }}</h3>
                                <span class="text-xs text-gray-400">Min PHP {{ $pkg['min_php'] }}+</span>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4">
                            {{ $pkg['description'] }}
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                        <span class="text-xs {{ $pkg['requires_db'] ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                            {{ $pkg['requires_db'] ? '● Auto DB Setup' : '○ Standalone' }}
                        </span>
                        <button wire:click="openInstallModal('{{ $pkg['slug'] }}')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-sm transition-all">
                            Install
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- INSTALLED APPS VIEW -->
    @if($activeTab === 'installed')
        @if($installedApps->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-800/50">
                <div class="mx-auto w-16 h-16 rounded-full bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">No Applications Installed</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                    Choose from our catalog to install applications onto your domains with 1 click.
                </p>
                <div class="mt-6">
                    <button wire:click="$set('activeTab', 'catalog')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-sm">
                        Browse Catalog
                    </button>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-4">Application</th>
                            <th class="px-6 py-4">Site URL</th>
                            <th class="px-6 py-4">Path</th>
                            <th class="px-6 py-4">Database</th>
                            <th class="px-6 py-4">Installed</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                        @foreach($installedApps as $app)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-xs">
                                            {{ substr($app->app_name, 0, 1) }}
                                        </span>
                                        <div>
                                            <div>{{ $app->app_name }}</div>
                                            <span class="text-xs text-gray-400 font-normal">v{{ $app->version }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ $app->site_url }}" target="_blank" class="text-sky-600 hover:text-sky-800 dark:hover:text-sky-400 font-medium">
                                        {{ $app->site_url }} &nearr;
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $app->install_path }}
                                </td>
                                <td class="px-6 py-4 text-xs font-mono">
                                    @if($app->db_name)
                                        <span class="text-gray-900 dark:text-gray-200 font-medium">{{ $app->db_name }}</span>
                                    @else
                                        <span class="text-gray-400">None</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $app->installed_at?->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="confirmUninstall({{ $app->id }})"
                                        class="px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg">
                                        Uninstall
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    <!-- 1-Click Install Modal -->
    @if($showInstallModal && $selected_package)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Install {{ $selected_package['name'] }}
                    </h3>
                    <button wire:click="$set('showInstallModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>

                <form wire:submit.prevent="installApp" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Target Domain</label>
                        <select wire:model="target_domain_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-sky-500 focus:border-sky-500 dark:text-gray-200">
                            @foreach($domains as $dom)
                                <option value="{{ $dom->id }}">{{ $dom->domain }}</option>
                            @endforeach
                        </select>
                        @error('target_domain_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Subdirectory (Optional)</label>
                        <input type="text" wire:model="sub_path"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-sky-500 focus:border-sky-500 dark:text-gray-200"
                            placeholder="e.g. app or portal (Leave empty to install in root)">
                        @error('sub_path') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Administrator Email</label>
                        <input type="email" wire:model="admin_email"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-sky-500 focus:border-sky-500 dark:text-gray-200">
                        @error('admin_email') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    @if($selected_package['requires_db'])
                        <div class="p-3 rounded-xl bg-sky-50 dark:bg-sky-900/30 border border-sky-100 dark:border-sky-800 text-xs text-sky-800 dark:text-sky-300">
                            <strong>Note:</strong> A dedicated isolated database, database user, and strong random password will be provisioned automatically for this installation.
                        </div>
                    @endif

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showInstallModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-sm">
                            Install Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Uninstall Confirmation Modal -->
    @if($showUninstallModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Uninstall Application?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Are you sure you want to uninstall this application?
                </p>
                <div class="mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="remove_files"
                            class="rounded border-gray-300 dark:border-gray-700 text-rose-600 focus:ring-rose-500">
                        <span class="text-xs text-gray-700 dark:text-gray-300">Also delete files from the installation directory</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showUninstallModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button wire:click="uninstallApp"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm">
                        Uninstall
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
