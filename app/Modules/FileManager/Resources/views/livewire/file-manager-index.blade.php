<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">File Manager</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Explore, edit, upload, and organize files within isolated user home directories.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-button wire:click="openCreateModal('file')" variant="secondary">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                New File
            </x-button>
            <x-button wire:click="openCreateModal('dir')" variant="secondary">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                New Folder
            </x-button>
        </div>
    </div>

    <!-- Navigation Bar & Account Switcher -->
    <x-card>
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <!-- Account Selector (if admin) -->
            @if($panel === 'admin')
                <div class="w-full lg:w-72">
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

            <!-- Breadcrumbs Trail -->
            <div class="flex-1 w-full bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-1.5 overflow-x-auto text-xs font-mono">
                <button 
                    wire:click="navigateTo('')" 
                    class="font-semibold text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    ~
                </button>

                @foreach($breadcrumbs as $b)
                    <span class="text-slate-400">/</span>
                    <button 
                        wire:click="navigateTo('{{ $b['path'] }}')" 
                        class="text-slate-700 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 font-medium"
                    >
                        {{ $b['name'] }}
                    </button>
                @endforeach
            </div>

            <!-- Up Button -->
            @if($currentPath !== '')
                <button 
                    wire:click="navigateUp" 
                    class="p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition"
                    title="Navigate up one folder"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    Up
                </button>
            @endif
        </div>
    </x-card>

    <!-- File Browser Table -->
    <x-card :noPadding="true">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Size</th>
                        <th class="px-6 py-4">Permissions</th>
                        <th class="px-6 py-4">Last Modified</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <!-- Name & Icon -->
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if($item['is_dir'])
                                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                        </svg>
                                        <button 
                                            wire:click="navigateTo('{{ $item['path'] }}')" 
                                            class="font-semibold text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 text-left font-mono text-xs"
                                        >
                                            {{ $item['name'] }}
                                        </button>
                                    @else
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <button 
                                            wire:click="openEditor('{{ $item['path'] }}')" 
                                            class="font-mono text-xs text-slate-700 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 text-left"
                                        >
                                            {{ $item['name'] }}
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Size -->
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-500">
                                {{ $item['size_formatted'] }}
                            </td>

                            <!-- Permissions -->
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-500">
                                {{ $item['permissions'] }}
                            </td>

                            <!-- Modified -->
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-400">
                                {{ $item['modified_at'] }}
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(!$item['is_dir'])
                                        <button 
                                            wire:click="openEditor('{{ $item['path'] }}')" 
                                            class="p-1.5 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                            title="Edit File"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    @endif

                                    @if($item['extension'] === 'zip')
                                        <button 
                                            wire:click="extractArchive('{{ $item['path'] }}')" 
                                            class="p-1.5 text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                            title="Extract Zip"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </button>
                                    @endif

                                    <button 
                                        wire:click="openRenameModal('{{ $item['path'] }}', '{{ $item['name'] }}')" 
                                        class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                        title="Rename"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <button 
                                        wire:click="deleteItem('{{ $item['path'] }}')" 
                                        wire:confirm="Are you sure you want to delete this {{ $item['is_dir'] ? 'directory and all its contents' : 'file' }}?"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                        title="Delete"
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
                                This folder is empty.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- File Editor Modal -->
    @if($showEditorModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-4xl w-full p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-mono">
                            Editing: {{ $editingFilePath }}
                        </h3>
                    </div>
                    <button wire:click="$set('showEditorModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="relative">
                    <textarea 
                        wire:model="editingFileContent" 
                        rows="18" 
                        class="w-full bg-slate-950 text-slate-100 p-4 rounded-xl font-mono text-xs leading-relaxed border border-slate-800 focus:ring-2 focus:ring-primary-500"
                        spellcheck="false"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <x-button type="button" wire:click="$set('showEditorModal', false)" variant="secondary">Cancel</x-button>
                    <x-button type="button" wire:click="saveFile" variant="primary">Save Changes</x-button>
                </div>
            </div>
        </div>
    @endif

    <!-- Create File / Directory Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Create New {{ $createType === 'dir' ? 'Directory' : 'File' }}
                </h3>

                <form wire:submit="createItem" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            {{ $createType === 'dir' ? 'Directory Name' : 'File Name' }} *
                        </label>
                        <input 
                            type="text" 
                            wire:model="createName" 
                            placeholder="{{ $createType === 'dir' ? 'assets' : 'index.php' }}" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono"
                        >
                        @error('createName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCreateModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Create</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Rename Modal -->
    @if($showRenameModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Rename Item</h3>

                <form wire:submit="renameItem" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">New Name *</label>
                        <input 
                            type="text" 
                            wire:model="renameNewName" 
                            class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5 font-mono"
                        >
                        @error('renameNewName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showRenameModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Rename</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
