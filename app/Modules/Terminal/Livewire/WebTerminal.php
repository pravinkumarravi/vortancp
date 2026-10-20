<?php

declare(strict_types=1);

namespace App\Modules\Terminal\Livewire;

use App\Modules\Terminal\Services\TerminalExecutionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WebTerminal extends Component
{
    public string $commandInput = '';
    public string $cwd = '';
    public array $history = [];
    public bool $isEntitled = true;
    public string $userLabel = 'vortan';
    public string $jailPath = '';
    public array $commandLog = []; // list of just command strings for up/down history recall

    public function mount(TerminalExecutionService $terminalService): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $this->isEntitled = $terminalService->isUserEntitled($user);
        $this->cwd = $terminalService->getDefaultWorkingDirectory($user);
        $this->jailPath = $terminalService->getCustomerJailPath($user);

        $username = $user->customer?->hostingAccounts()->first()?->username ?? explode('@', $user->email)[0];
        $this->userLabel = $user->hasRole('customer') ? "{$username}@vortan" : 'root@vortan';

        $this->history = [
            [
                'id' => 'init-banner',
                'command' => null,
                'output' => "VortanCP Web Terminal v1.0.0 (x86_64-vortan-linux-gnu)\nType 'help' or use quick presets above for common operations.\nSession authenticated as: " . $this->userLabel . "\n",
                'exit_code' => 0,
                'cwd' => $this->cwd,
                'duration_ms' => 0,
                'time' => now()->format('H:i:s'),
            ],
        ];
    }

    public function execute(TerminalExecutionService $terminalService): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $rawCommand = trim($this->commandInput);
        if ($rawCommand === '') {
            $this->commandInput = '';
            return;
        }

        // Help command
        if ($rawCommand === 'help') {
            $this->commandLog[] = $rawCommand;
            $this->history[] = [
                'id' => uniqid('cmd_'),
                'command' => 'help',
                'output' => "VortanCP Terminal Built-in Commands:\n" .
                    "  help          Show this help dialogue\n" .
                    "  clear         Clear terminal output screen\n" .
                    "  cd [path]     Change current working directory\n" .
                    "  pwd           Print current working directory\n" .
                    "  ls / dir      List directory contents\n" .
                    "  php -v        Display active PHP runtime\n" .
                    "  composer -v   Check Composer package manager\n" .
                    "  git status    Inspect working tree status\n",
                'exit_code' => 0,
                'cwd' => $this->cwd,
                'duration_ms' => 0.5,
                'time' => now()->format('H:i:s'),
            ];
            $this->commandInput = '';
            return;
        }

        // Clear command
        if ($rawCommand === 'clear') {
            $this->clearScreen();
            $this->commandInput = '';
            return;
        }

        $this->commandLog[] = $rawCommand;

        $result = $terminalService->execute($user, $rawCommand, $this->cwd);

        if ($result['is_directory_change']) {
            $this->cwd = $result['cwd'];
        }

        $this->history[] = [
            'id' => uniqid('cmd_'),
            'command' => $result['command'],
            'output' => $result['output'],
            'exit_code' => $result['exit_code'],
            'cwd' => $this->cwd,
            'duration_ms' => $result['duration_ms'],
            'time' => now()->format('H:i:s'),
        ];

        $this->commandInput = '';
    }

    public function runPreset(string $cmd, TerminalExecutionService $terminalService): void
    {
        $this->commandInput = $cmd;
        $this->execute($terminalService);
    }

    public function clearScreen(): void
    {
        $this->history = [];
    }

    public function resetTerminal(TerminalExecutionService $terminalService): void
    {
        $user = Auth::user();
        if ($user) {
            $this->cwd = $terminalService->getDefaultWorkingDirectory($user);
        }

        $this->history = [
            [
                'id' => uniqid('reset_'),
                'command' => null,
                'output' => "Terminal session reset.\nAuthenticated as: " . $this->userLabel . "\n",
                'exit_code' => 0,
                'cwd' => $this->cwd,
                'duration_ms' => 0,
                'time' => now()->format('H:i:s'),
            ],
        ];
        $this->commandInput = '';
    }

    public function render(): View
    {
        return view('terminal::livewire.web-terminal')
            ->layout('layouts.app', [
                'title' => 'Web Terminal',
            ]);
    }
}
