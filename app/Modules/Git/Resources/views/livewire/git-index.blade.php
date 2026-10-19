<div class="space-y-6">
    <!-- Header with Title & Action -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <span class="p-2 rounded-lg bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                    </svg>
                </span>
                Git Repository Manager
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manage local and remote-tracking Git repositories, configure auto-deployment webhooks, and push code seamlessly.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(count($accounts) > 0)
                <div class="flex items-center gap-2">
                    <label for="acc_select" class="text-xs font-semibold text-gray-500 uppercase">Account:</label>
                    <select id="acc_select" wire:model.live="selectedAccountId"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm py-2 px-3 focus:ring-orange-500 focus:border-orange-500 dark:text-gray-200">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->username }} ({{ $acc->primary_domain }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Repository
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
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

    <!-- Repositories List -->
    @if($repositories->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-800/50">
            <div class="mx-auto w-16 h-16 rounded-full bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">No Git Repositories</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                No Git repositories configured for this account yet. Create a local repository or connect to GitHub/GitLab.
            </p>
            <div class="mt-6">
                <button wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm">
                    Create Repository Now
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6">
            @foreach($repositories as $repo)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700/60">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $repo->name }}</h3>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        <svg class="w-3 h-3 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ $repo->branch }}
                                    </span>
                                    @if($repo->auto_deploy)
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            Auto-Deploy ON
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if($repo->remote_url)
                                        <span class="font-mono text-gray-700 dark:text-gray-300">{{ $repo->remote_url }}</span>
                                    @else
                                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Local Bare Repository</span>
                                    @endif
                                    <span>&bull;</span>
                                    <span>Target: <span class="font-mono">{{ $repo->deploy_path }}</span></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button wire:click="deployNow({{ $repo->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Deploy Now
                                </button>

                                <button wire:click="viewDeployKey({{ $repo->id }})"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    SSH Key
                                </button>

                                <button wire:click="viewWebhook({{ $repo->id }})"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Webhook
                                </button>

                                <button wire:click="confirmDelete({{ $repo->id }})"
                                    class="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Last Commit / Deployment Details -->
                        <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800">
                            @if($repo->last_deployed_at)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                    <div>
                                        <span class="font-semibold text-gray-700 dark:text-gray-300">Last Commit:</span>
                                        <span class="font-mono text-orange-600 dark:text-orange-400 font-bold ml-1">
                                            {{ substr($repo->last_commit_hash ?? '', 0, 8) }}
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-400 ml-2">&ldquo;{{ $repo->last_commit_message }}&rdquo;</span>
                                    </div>
                                    <div class="text-gray-400">
                                        Deployed: {{ $repo->last_deployed_at->diffForHumans() }} by {{ $repo->last_commit_author }}
                                    </div>
                                </div>
                            @else
                                <div class="text-xs text-gray-400">
                                    No deployments performed yet. Click <span class="font-semibold text-gray-600 dark:text-gray-300">Deploy Now</span> or configure a git push webhook.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Create Repository Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create Git Repository</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>

                <form wire:submit.prevent="createRepository" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Repository Name</label>
                            <input type="text" wire:model="name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-orange-500 focus:border-orange-500 dark:text-gray-200"
                                placeholder="my-app">
                            @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Branch</label>
                            <input type="text" wire:model="branch"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-orange-500 focus:border-orange-500 dark:text-gray-200"
                                placeholder="main">
                            @error('branch') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Remote URL (Optional)</label>
                        <input type="text" wire:model="remote_url"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-orange-500 focus:border-orange-500 dark:text-gray-200 font-mono"
                            placeholder="git@github.com:username/repository.git (Leave empty for local repo)">
                        @error('remote_url') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Attach to Domain (Document Root)</label>
                        <select wire:model="domain_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-orange-500 focus:border-orange-500 dark:text-gray-200">
                            <option value="">-- Custom Deploy Path --</option>
                            @foreach($domains as $dom)
                                <option value="{{ $dom->id }}">{{ $dom->domain }} ({{ $dom->document_root }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Custom Deploy Path (Optional)</label>
                        <input type="text" wire:model="deploy_path"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-orange-500 focus:border-orange-500 dark:text-gray-200 font-mono"
                            placeholder="Leave empty to use domain document root">
                        @error('deploy_path') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Post-Receive Build Script</label>
                        <textarea wire:model="deploy_script" rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-xs font-mono focus:ring-orange-500 focus:border-orange-500 dark:text-gray-200"></textarea>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="auto_deploy"
                                class="rounded border-gray-300 dark:border-gray-700 text-orange-600 focus:ring-orange-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Automatically deploy when changes are pushed</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm">
                            Create Repository
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- SSH Deploy Key Modal -->
    @if($showKeyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-xl p-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        SSH Deploy Key
                    </h3>
                    <button wire:click="$set('showKeyModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Copy and add this public key to your repository's <strong>Deploy Keys</strong> settings in GitHub, GitLab, or Bitbucket with read access.
                </p>
                <div class="p-3 bg-gray-900 rounded-lg text-emerald-400 font-mono text-xs break-all select-all">
                    {{ $viewing_public_key }}
                </div>
                <div class="mt-6 flex justify-end">
                    <button wire:click="$set('showKeyModal', false)"
                        class="px-4 py-2 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Webhook Modal -->
    @if($showWebhookModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-xl p-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Automatic Deploy Webhook
                    </h3>
                    <button wire:click="$set('showWebhookModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>
                <div class="space-y-4">
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 uppercase mb-1">Payload URL</span>
                        <div class="p-2.5 bg-gray-100 dark:bg-gray-900 rounded-lg text-gray-800 dark:text-gray-200 font-mono text-xs select-all break-all">
                            {{ $viewing_webhook_url }}
                        </div>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 uppercase mb-1">Secret Token</span>
                        <div class="p-2.5 bg-gray-100 dark:bg-gray-900 rounded-lg text-gray-800 dark:text-gray-200 font-mono text-xs select-all">
                            {{ $viewing_webhook_secret }}
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button wire:click="$set('showWebhookModal', false)"
                        class="px-4 py-2 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Deploy Output Log Modal -->
    @if($showDeployOutputModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl p-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Deployment Execution Log</h3>
                    <button wire:click="$set('showDeployOutputModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
                </div>
                <pre class="p-4 bg-gray-950 rounded-xl text-emerald-400 font-mono text-xs overflow-auto max-h-96 leading-relaxed">{{ $deploy_log }}</pre>
                <div class="mt-6 flex justify-end">
                    <button wire:click="$set('showDeployOutputModal', false)"
                        class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg">
                        Close Log
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Delete Git Repository?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Are you sure you want to delete this repository? The git repository files will be removed from your hosting account.
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button wire:click="deleteRepository"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
