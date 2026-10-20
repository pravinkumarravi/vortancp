<?php

declare(strict_types=1);

namespace App\Modules\EmailSecurity\Livewire;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\EmailSecurity\Models\EmailSecurityPolicy;
use App\Modules\EmailSecurity\Services\EmailSecurityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmailSecurityManager extends Component
{
    public ?int $policyId = null;
    public bool $spam_filter_enabled = true;
    public bool $greylisting_enabled = true;
    public float $required_score = 5.0;
    public float $reject_score = 15.0;
    public bool $auto_delete_spam = false;
    public string $spam_subject_tag = '***SPAM***';

    public string $newAllowlistEntry = '';
    public string $newBlocklistEntry = '';

    // Simulator properties
    public string $testSender = 'promotions@spammer.xyz';
    public string $testSubject = 'CONGRATULATIONS WINNER! CLAIM YOUR FREE MONEY NOW!!!';
    public string $testBody = 'Please confirm your password to receive the wire transfer.';
    public ?array $testResult = null;

    public function mount(EmailSecurityService $service): void
    {
        $user = Auth::user();
        $account = null;

        if ($user && $user->hasRole('customer')) {
            $account = $user->customer?->hostingAccounts()->first();
        } else {
            $account = HostingAccount::first();
        }

        if ($account) {
            $policy = $service->getOrCreatePolicy($account);
            $this->policyId = $policy->id;
            $this->spam_filter_enabled = $policy->spam_filter_enabled;
            $this->greylisting_enabled = $policy->greylisting_enabled;
            $this->required_score = (float) $policy->required_score;
            $this->reject_score = (float) $policy->reject_score;
            $this->auto_delete_spam = $policy->auto_delete_spam;
            $this->spam_subject_tag = $policy->spam_subject_tag;
        }
    }

    public function savePolicy(): void
    {
        $this->validate([
            'required_score' => 'required|numeric|min:1|max:20',
            'reject_score' => 'required|numeric|min:5|max:50',
            'spam_subject_tag' => 'required|string|max:50',
        ]);

        if ($this->policyId) {
            $policy = EmailSecurityPolicy::find($this->policyId);
            if ($policy) {
                $policy->update([
                    'spam_filter_enabled' => $this->spam_filter_enabled,
                    'greylisting_enabled' => $this->greylisting_enabled,
                    'required_score' => $this->required_score,
                    'reject_score' => $this->reject_score,
                    'auto_delete_spam' => $this->auto_delete_spam,
                    'spam_subject_tag' => $this->spam_subject_tag,
                ]);

                session()->flash('success', 'Email anti-spam and security thresholds updated successfully.');
            }
        }
    }

    public function addAllowlist(EmailSecurityService $service): void
    {
        if ($this->policyId && trim($this->newAllowlistEntry) !== '') {
            $policy = EmailSecurityPolicy::find($this->policyId);
            if ($policy) {
                $service->addAllowlistEntry($policy, $this->newAllowlistEntry);
                $this->newAllowlistEntry = '';
                session()->flash('success', 'Address added to trusted allowlist.');
            }
        }
    }

    public function removeAllowlist(string $entry, EmailSecurityService $service): void
    {
        if ($this->policyId) {
            $policy = EmailSecurityPolicy::find($this->policyId);
            if ($policy) {
                $service->removeAllowlistEntry($policy, $entry);
                session()->flash('success', 'Address removed from allowlist.');
            }
        }
    }

    public function addBlocklist(EmailSecurityService $service): void
    {
        if ($this->policyId && trim($this->newBlocklistEntry) !== '') {
            $policy = EmailSecurityPolicy::find($this->policyId);
            if ($policy) {
                $service->addBlocklistEntry($policy, $this->newBlocklistEntry);
                $this->newBlocklistEntry = '';
                session()->flash('success', 'Address added to sender blocklist.');
            }
        }
    }

    public function removeBlocklist(string $entry, EmailSecurityService $service): void
    {
        if ($this->policyId) {
            $policy = EmailSecurityPolicy::find($this->policyId);
            if ($policy) {
                $service->removeBlocklistEntry($policy, $entry);
                session()->flash('success', 'Address removed from blocklist.');
            }
        }
    }

    public function evaluateTestMessage(EmailSecurityService $service): void
    {
        if ($this->policyId) {
            $policy = EmailSecurityPolicy::find($this->policyId);
            if ($policy) {
                $this->testResult = $service->evaluateMessage(
                    $this->testSender,
                    $this->testSubject,
                    $this->testBody,
                    $policy
                );
            }
        }
    }

    public function render(): View
    {
        $policy = $this->policyId ? EmailSecurityPolicy::find($this->policyId) : null;

        return view('email_security::livewire.email-security-manager', [
            'policy' => $policy,
        ])->layout('layouts.app', [
            'title' => 'Email Security & Anti-Spam',
        ]);
    }
}
