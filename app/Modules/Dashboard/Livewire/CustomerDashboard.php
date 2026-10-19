<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'customer'])]
#[Title('Customer Hosting Panel - VortanCP')]
class CustomerDashboard extends Component
{
    public function render(): View
    {
        return view('dashboard::livewire.customer-dashboard');
    }
}
