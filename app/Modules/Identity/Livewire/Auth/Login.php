<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Auth;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Identity\Services\TotpService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Sign In - VortanCP')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    // Two-factor state
    public bool $showTwoFactorStep = false;
    public string $twoFactorCode = '';
    public ?int $pendingUserId = null;

    protected function rules(): array
    {
        if ($this->showTwoFactorStep) {
            return [
                'twoFactorCode' => ['required', 'string', 'size:6'],
            ];
        }

        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(): void
    {
        $this->validate();

        $throttleKey = Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many failed login attempts. Please try again in {$seconds} seconds.");
            return;
        }

        $user = User::where('email', $this->email)->first();

        if (!$user || !Auth::validate(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey, 900); // 15-minute lock

            app(AuditLogger::class)->log(
                event: 'user.login_failed',
                oldValues: ['email' => $this->email],
                userId: $user?->id
            );

            $this->addError('email', 'These credentials do not match our records.');
            return;
        }

        // Check if user has TOTP 2FA enabled
        if ($user->isTwoFactorEnabled()) {
            $this->pendingUserId = $user->id;
            $this->showTwoFactorStep = true;
            return;
        }

        $this->completeLogin($user, $throttleKey);
    }

    public function verifyTwoFactor(): void
    {
        $this->validate([
            'twoFactorCode' => ['required', 'string'],
        ]);

        $user = User::findOrFail($this->pendingUserId);
        $totp = app(TotpService::class);

        $secret = $user->twoFactor?->secret;

        if (!$secret || !$totp->verifyCode($secret, $this->twoFactorCode)) {
            $this->addError('twoFactorCode', 'The two-factor authentication code is invalid.');
            return;
        }

        $throttleKey = Str::lower($user->email) . '|' . request()->ip();
        $this->completeLogin($user, $throttleKey);
    }

    protected function completeLogin(User $user, string $throttleKey): void
    {
        RateLimiter::clear($throttleKey);
        Auth::login($user, $this->remember);
        session()->regenerate();

        app(AuditLogger::class)->log(
            event: 'user.logged_in',
            auditable: $user,
            userId: $user->id
        );

        $this->redirectIntended($this->resolveTargetDashboard($user));
    }

    public function fillCredentials(string $role): void
    {
        match ($role) {
            'admin' => [
                $this->email = 'admin@vortan.local',
                $this->password = 'VortanAdmin#2026!',
            ],
            'reseller' => [
                $this->email = 'reseller@vortan.local',
                $this->password = 'VortanReseller#2026!',
            ],
            'customer' => [
                $this->email = 'customer@vortan.local',
                $this->password = 'VortanCustomer#2026!',
            ],
            default => null,
        };
    }

    protected function resolveTargetDashboard(User $user): string
    {
        if ($user->isSuperAdmin() || $user->isServerAdmin()) {
            return route('admin.dashboard');
        }

        if ($user->isReseller()) {
            return route('reseller.dashboard');
        }

        return route('customer.dashboard');
    }

    public function render(): View
    {
        return view('identity::livewire.auth.login');
    }
}
