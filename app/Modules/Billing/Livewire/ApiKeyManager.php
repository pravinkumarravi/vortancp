<?php

declare(strict_types=1);

namespace App\Modules\Billing\Livewire;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Billing\Models\ApiKey;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('API Keys & Billing Integration - VortanCP')]
class ApiKeyManager extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    // Create Modal State
    public bool $showCreateModal = false;
    public string $keyName = '';
    public string $selectedAbility = '*';
    public ?string $expiresAt = null;

    // Generated Token Flash State
    public ?string $plainTextToken = null;
    public ?string $createdKeyName = null;

    protected function rules(): array
    {
        return [
            'keyName' => ['required', 'string', 'min:3', 'max:100'],
            'selectedAbility' => ['required', 'string'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function generateKey(): void
    {
        $this->authorize('servers.manage');
        $this->validate();

        $abilities = $this->selectedAbility === '*' ? ['*'] : [$this->selectedAbility];
        $expiration = $this->expiresAt ? now()->parse($this->expiresAt) : null;

        $result = ApiKey::generate(
            name: $this->keyName,
            abilities: $abilities,
            expiresAt: $expiration
        );

        $this->plainTextToken = $result['plainTextToken'];
        $this->createdKeyName = $this->keyName;

        app(AuditLogger::class)->log(
            event: 'api_key.generated',
            auditable: $result['apiKey'],
            newValues: ['name' => $this->keyName, 'abilities' => $abilities]
        );

        $this->reset(['showCreateModal', 'keyName', 'selectedAbility', 'expiresAt']);
        session()->flash('success', "API Key created successfully. Copy the token now; it will not be shown again.");
    }

    public function dismissToken(): void
    {
        $this->plainTextToken = null;
        $this->createdKeyName = null;
    }

    public function toggleKeyStatus(int $id): void
    {
        $this->authorize('servers.manage');

        $key = ApiKey::findOrFail($id);
        $key->update(['is_active' => !$key->is_active]);

        app(AuditLogger::class)->log(
            event: 'api_key.status_toggled',
            auditable: $key,
            newValues: ['is_active' => $key->is_active]
        );

        session()->flash('success', "API Key status updated.");
    }

    public function deleteKey(int $id): void
    {
        $this->authorize('servers.manage');

        $key = ApiKey::findOrFail($id);
        $name = $key->name;
        $key->delete();

        app(AuditLogger::class)->log(
            event: 'api_key.deleted',
            auditable: $key,
            newValues: ['name' => $name]
        );

        session()->flash('success', "API Key [{$name}] revoked and deleted permanently.");
    }

    public function render(): View
    {
        $keys = ApiKey::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('key_prefix', 'like', "%{$this->search}%"))
            ->latest('id')
            ->get();

        return view('billing::livewire.api-key-manager', [
            'keys' => $keys,
        ]);
    }
}
