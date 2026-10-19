<?php

declare(strict_types=1);

namespace App\Modules\Dns\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Dns\Services\DnsZoneService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('DNS Zone Editor - VortanCP')]
class DnsZoneEditor extends Component
{
    use AuthorizesRequests;

    public string $panel = 'admin';
    public ?int $selectedZoneId = null;
    public string $search = '';
    public string $typeFilter = 'all';

    // Add / Edit Record Modal State
    public bool $showRecordModal = false;
    public ?int $editingRecordId = null;
    public string $recordName = '@';
    public string $recordType = DnsRecord::TYPE_A;
    public string $recordContent = '';
    public int $recordTtl = 3600;
    public ?int $recordPriority = null;

    // View Raw Zone Modal State
    public bool $showRawZoneModal = false;
    public string $rawZoneContent = '';

    protected function rules(): array
    {
        return [
            'recordName' => ['required', 'string', 'max:255'],
            'recordType' => ['required', 'in:A,AAAA,CNAME,MX,TXT,SRV,CAA,NS'],
            'recordContent' => ['required', 'string'],
            'recordTtl' => ['required', 'integer', 'min:60'],
            'recordPriority' => ['nullable', 'required_if:recordType,MX', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function mount(string $panel = 'admin', ?int $zoneId = null): void
    {
        $this->panel = $panel;
        $user = auth()->user();

        if ($zoneId) {
            $this->selectedZoneId = $zoneId;
        } else {
            $query = DnsZone::query();
            if ($this->panel === 'customer' || $user->isCustomer()) {
                $query->whereHas('hostingAccount.customer', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            $firstZone = $query->first();
            $this->selectedZoneId = $firstZone?->id;
        }
    }

    public function openAddModal(): void
    {
        $this->resetValidation();
        $this->editingRecordId = null;
        $this->recordName = '@';
        $this->recordType = DnsRecord::TYPE_A;
        $this->recordContent = '';
        $this->recordTtl = 3600;
        $this->recordPriority = null;
        $this->showRecordModal = true;
    }

    public function openEditModal(int $recordId): void
    {
        $this->resetValidation();
        $record = DnsRecord::findOrFail($recordId);

        $this->editingRecordId = $record->id;
        $this->recordName = $record->name;
        $this->recordType = $record->type;
        $this->recordContent = $record->content;
        $this->recordTtl = $record->ttl;
        $this->recordPriority = $record->priority;

        $this->showRecordModal = true;
    }

    public function saveRecord(DnsZoneService $dnsService): void
    {
        $this->authorize('dns.manage');
        $this->validate();

        $zone = DnsZone::findOrFail($this->selectedZoneId);

        try {
            $data = [
                'name' => $this->recordName,
                'type' => $this->recordType,
                'content' => $this->recordContent,
                'ttl' => $this->recordTtl,
                'priority' => $this->recordPriority,
            ];

            if ($this->editingRecordId) {
                $record = DnsRecord::findOrFail($this->editingRecordId);
                $dnsService->updateRecord($record, $data);
                $msg = "Record [{$record->name}] updated successfully.";
            } else {
                $record = $dnsService->addRecord($zone, $data);
                $msg = "Record [{$record->name}] created successfully.";
            }

            $this->showRecordModal = false;
            session()->flash('success', $msg);
        } catch (\Exception $e) {
            $this->addError('recordContent', $e->getMessage());
        }
    }

    public function deleteRecord(int $recordId, DnsZoneService $dnsService): void
    {
        $this->authorize('dns.manage');

        $record = DnsRecord::findOrFail($recordId);
        $name = $record->name;
        $type = $record->type;

        $dnsService->deleteRecord($record);
        session()->flash('success', "Record [{$name} IN {$type}] deleted successfully.");
    }

    public function viewRawZone(DnsZoneService $dnsService): void
    {
        $zone = DnsZone::findOrFail($this->selectedZoneId);
        $this->rawZoneContent = $dnsService->exportZoneFile($zone);
        $this->showRawZoneModal = true;
    }

    public function render(): View
    {
        $user = auth()->user();

        $zonesQuery = DnsZone::query();
        if ($this->panel === 'customer' || $user->isCustomer()) {
            $zonesQuery->whereHas('hostingAccount.customer', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        $availableZones = $zonesQuery->get();

        $currentZone = $this->selectedZoneId ? DnsZone::with('records')->find($this->selectedZoneId) : null;

        $records = $currentZone ? $currentZone->records()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('content', 'like', "%{$this->search}%");
                });
            })
            ->when($this->typeFilter !== 'all', function ($q) {
                $q->where('type', $this->typeFilter);
            })
            ->get() : collect();

        return view('dns::livewire.dns-zone-editor', [
            'availableZones' => $availableZones,
            'currentZone' => $currentZone,
            'records' => $records,
        ]);
    }
}
