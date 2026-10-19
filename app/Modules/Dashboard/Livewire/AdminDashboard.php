<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Livewire;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', ['panel' => 'admin'])]
#[Title('Administrator Overview - VortanCP')]
class AdminDashboard extends Component
{
    public function render(): View
    {
        $totalUsers = User::count();
        $recentAuditLogs = AuditLog::with('user')->latest('id')->take(6)->get();

        // Sample real system telemetry
        $systemLoad = function_exists('sys_getloadavg') ? (sys_getloadavg()[0] ?? 0.25) : 0.35;

        return view('dashboard::livewire.admin-dashboard', [
            'totalUsers' => $totalUsers,
            'recentAuditLogs' => $recentAuditLogs,
            'systemLoad' => $systemLoad,
        ]);
    }
}
