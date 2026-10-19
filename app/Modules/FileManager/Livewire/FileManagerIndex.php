<?php

declare(strict_types=1);

namespace App\Modules\FileManager\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\FileManager\Services\FilesystemJailService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('File Manager - VortanCP')]
class FileManagerIndex extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $panel = 'admin';
    public ?int $selectedAccountId = null;
    public string $currentPath = '';

    // File Editor Modal
    public bool $showEditorModal = false;
    public string $editingFilePath = '';
    public string $editingFileContent = '';

    // Create File / Directory Modal
    public bool $showCreateModal = false;
    public string $createType = 'file'; // 'file' or 'dir'
    public string $createName = '';

    // Rename Modal
    public bool $showRenameModal = false;
    public string $renameOldPath = '';
    public string $renameNewName = '';

    // Upload Modal
    public bool $showUploadModal = false;
    public $uploadedFile;

    public function mount(string $panel = 'admin', ?int $accountId = null): void
    {
        $this->panel = $panel;
        $user = auth()->user();

        if ($accountId) {
            $this->selectedAccountId = $accountId;
        } else {
            $query = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE);
            if ($this->panel === 'customer' || $user->isCustomer()) {
                $query->whereHas('customer', fn($q) => $q->where('user_id', $user->id));
            }
            $this->selectedAccountId = $query->first()?->id;
        }
    }

    public function navigateTo(string $path): void
    {
        $this->currentPath = trim($path, '/');
    }

    public function navigateUp(): void
    {
        if ($this->currentPath === '') {
            return;
        }

        $parts = explode('/', $this->currentPath);
        array_pop($parts);
        $this->currentPath = implode('/', $parts);
    }

    public function openEditor(string $relativePath, FilesystemJailService $fs): void
    {
        $this->authorize('files.manage');
        $account = HostingAccount::findOrFail($this->selectedAccountId);

        try {
            $this->editingFilePath = $relativePath;
            $this->editingFileContent = $fs->readFile($account, $relativePath);
            $this->showEditorModal = true;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function saveFile(FilesystemJailService $fs): void
    {
        $this->authorize('files.manage');
        $account = HostingAccount::findOrFail($this->selectedAccountId);

        try {
            $fs->writeFile($account, $this->editingFilePath, $this->editingFileContent);
            $this->showEditorModal = false;
            session()->flash('success', "File [{$this->editingFilePath}] saved successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openCreateModal(string $type = 'file'): void
    {
        $this->createType = $type;
        $this->createName = '';
        $this->showCreateModal = true;
    }

    public function createItem(FilesystemJailService $fs): void
    {
        $this->authorize('files.manage');
        $this->validate(['createName' => ['required', 'string', 'max:128']]);

        $account = HostingAccount::findOrFail($this->selectedAccountId);
        $target = trim("{$this->currentPath}/{$this->createName}", '/');

        try {
            if ($this->createType === 'dir') {
                $fs->createDirectory($account, $target);
                $msg = "Directory [{$this->createName}] created successfully.";
            } else {
                $fs->writeFile($account, $target, '');
                $msg = "File [{$this->createName}] created successfully.";
            }

            $this->showCreateModal = false;
            session()->flash('success', $msg);
        } catch (\Exception $e) {
            $this->addError('createName', $e->getMessage());
        }
    }

    public function openRenameModal(string $relativePath, string $currentName): void
    {
        $this->renameOldPath = $relativePath;
        $this->renameNewName = $currentName;
        $this->showRenameModal = true;
    }

    public function renameItem(FilesystemJailService $fs): void
    {
        $this->authorize('files.manage');
        $this->validate(['renameNewName' => ['required', 'string', 'max:128']]);

        $account = HostingAccount::findOrFail($this->selectedAccountId);
        $parent = dirname($this->renameOldPath);
        $target = $parent === '.' || $parent === '' ? $this->renameNewName : "{$parent}/{$this->renameNewName}";

        try {
            $fs->renameItem($account, $this->renameOldPath, $target);
            $this->showRenameModal = false;
            session()->flash('success', "Item renamed to [{$this->renameNewName}].");
        } catch (\Exception $e) {
            $this->addError('renameNewName', $e->getMessage());
        }
    }

    public function deleteItem(string $relativePath, FilesystemJailService $fs): void
    {
        $this->authorize('files.manage');
        $account = HostingAccount::findOrFail($this->selectedAccountId);

        try {
            $fs->deleteItem($account, $relativePath);
            session()->flash('success', "Item [{$relativePath}] deleted.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function extractArchive(string $relativePath, FilesystemJailService $fs): void
    {
        $this->authorize('files.manage');
        $account = HostingAccount::findOrFail($this->selectedAccountId);

        try {
            $fs->extractZip($account, $relativePath, $this->currentPath);
            session()->flash('success', "Archive [{$relativePath}] extracted into current directory.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(FilesystemJailService $fs): View
    {
        $user = auth()->user();

        $accountsQuery = HostingAccount::where('status', HostingAccount::STATUS_ACTIVE);
        if ($this->panel === 'customer' || $user->isCustomer()) {
            $accountsQuery->whereHas('customer', fn($q) => $q->where('user_id', $user->id));
        }
        $availableAccounts = $accountsQuery->get();

        $account = $this->selectedAccountId ? HostingAccount::find($this->selectedAccountId) : null;
        $items = [];
        $error = null;

        if ($account) {
            try {
                $items = $fs->listDirectory($account, $this->currentPath);
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->currentPath = '';
            }
        }

        // Build breadcrumb segments
        $breadcrumbs = [];
        if ($this->currentPath !== '') {
            $parts = explode('/', $this->currentPath);
            $accum = '';
            foreach ($parts as $p) {
                $accum = trim("{$accum}/{$p}", '/');
                $breadcrumbs[] = ['name' => $p, 'path' => $accum];
            }
        }

        return view('filemanager::livewire.file-manager-index', [
            'availableAccounts' => $availableAccounts,
            'currentAccount' => $account,
            'items' => $items,
            'breadcrumbs' => $breadcrumbs,
            'error' => $error,
        ]);
    }
}
