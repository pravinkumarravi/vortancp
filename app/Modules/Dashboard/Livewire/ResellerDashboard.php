<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'reseller'])]
#[Title('Reseller Center - VortanCP')]
class ResellerDashboard extends Component
{
    public function render(): View
    {
        return view('dashboard::livewire.reseller-dashboard');
    }
}
