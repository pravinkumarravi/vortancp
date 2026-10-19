# VortanCP Livewire, Alpine.js & TailAdmin UI Design Patterns

## 1. Architectural Role Division

To maintain exceptional code cleanliness, performance, and UI responsiveness, VortanCP establishes a strict separation of concerns among the frontend technologies:

| Layer / Technology | Explicit Responsibilities | Forbidden Patterns |
| :--- | :--- | :--- |
| **Livewire (v4)** | Server-side data fetching, pagination, database queries, business mutations, form submissions, policy checks, server validation, error dispatching. | Writing ad-hoc shell commands, complex layout styling directly in class logic, duplicating Alpine client state. |
| **Alpine.js** | Ephemeral browser-only micro-interactions: dropdown menus, mobile navigation drawer toggle, modal open/close animations, command palette (`Ctrl+K`), local tab selection. | Storing database models, making raw unauthenticated fetch requests to sensitive endpoints, duplicating server business validation. |
| **Blade** | Semantic HTML structure, TailAdmin layout frames, component composition, accessibility attributes (ARIA), SVG icon inclusions. | Complex PHP business logic, database queries (`@php User::all() @endphp`), unescaped user output (`{!! !!}`). |
| **TailAdmin / Tailwind CSS** | Consistent visual design system, dark/light theme switching, typography, responsive grids, transitions, loading states. | Inline `style="..."` attributes, arbitrary inconsistent hex colors. |

---

## 2. Standardized Livewire Component Taxonomy

Every module follows a standardized five-tier component structure:

```
app/Modules/{ModuleName}/Livewire/
├── Pages/          # Route-level components (extends TailAdmin app layout)
├── Tables/         # Data tables with search, sorting, filtering & pagination
├── Forms/          # Reusable create/edit forms with real-time validation
├── Modals/         # Single-purpose action confirmation & destructive modals
└── Components/     # Reusable telemetry widgets, status badges, quick actions
```

---

## 3. Reference Implementation: Class-Based Livewire Component

Below is the standard pattern implemented across all VortanCP modules (demonstrated with `DomainTable`):

```php
namespace App\Modules\Domains\Livewire\Tables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Domains\Models\Domain;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DomainTable extends Component
{
    use WithPagination, AuthorizesRequests;

    // Component Public State
    public string $search = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public string $statusFilter = 'all';

    // Query String Synchronization (Clean URLs)
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    // Reset pagination on search update
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // Sort Handler
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Action with Server-Side Authorization
    public function triggerDeleteModal(int $domainId): void
    {
        $domain = Domain::findOrFail($domainId);
        $this->authorize('delete', $domain);

        // Dispatch browser / sibling event to open confirm modal
        $this->dispatch('open-modal', 'delete-domain-modal', domainId: $domainId, domainName: $domain->name);
    }

    public function render(): View
    {
        $user = auth()->user();

        $domains = Domain::query()
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                $query->whereIn('hosting_account_id', $user->accessibleAccountIds());
            })
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('domains::livewire.tables.domain-table', [
            'domains' => $domains,
        ]);
    }
}
```

---

## 4. UI Polish & Reactive Loading States

To guarantee that the user interface feels instantaneous and never leaves the operator wondering if an action succeeded:

### 4.1 Loading Skeletons & Targets
Every button and asynchronous action utilizes Tailwind transitions and Livewire targets:

```blade
<button 
    wire:click="rebuildVirtualHost({{ $domain->id }})"
    wire:loading.attr="disabled"
    wire:target="rebuildVirtualHost({{ $domain->id }})"
    class="btn-primary inline-flex items-center gap-2"
>
    <!-- Normal State Icon -->
    <svg wire:loading.remove wire:target="rebuildVirtualHost({{ $domain->id }})" class="w-4 h-4" ...></svg>
    
    <!-- Spinner during Execution -->
    <svg wire:loading wire:target="rebuildVirtualHost({{ $domain->id }})" class="animate-spin w-4 h-4 text-white" ...></svg>
    
    <span>Rebuild VHost</span>
</button>
```

### 4.2 Dynamic TailAdmin Badges & Status Indicators
Status indicators (e.g. `Active`, `Pending`, `Failed`, `Suspended`) are encapsulated in reusable Blade components (`x-status-badge`):
- `Active` -> Emerald green badge with subtle pulse dot.
- `Pending` -> Amber badge with rotating gear or spinner.
- `Failed` -> Rose red badge with error tooltip.
- `Suspended` -> Slate grey badge with padlock icon.

---

## 5. Client-Side Interactions with Alpine.js

Alpine.js is leveraged exclusively for client transitions without server latency:

```blade
<!-- Reusable TailAdmin Dropdown -->
<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
        <span class="text-sm font-medium">Actions</span>
        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" ...></svg>
    </button>

    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 w-48 mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 py-1"
        style="display: none;"
    >
        <button wire:click="edit" class="dropdown-item">Edit Settings</button>
        <button wire:click="manageDns" class="dropdown-item">DNS Zone</button>
        <div class="h-px bg-gray-100 dark:bg-gray-700 my-1"></div>
        <button wire:click="triggerDelete" class="dropdown-item text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30">Delete</button>
    </div>
</div>
```

This guarantees instantaneous dropdown response times while Livewire handles all real server data and authorization.
