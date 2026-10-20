<?php

declare(strict_types=1);

namespace App\Modules\EmailSecurity\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Email\Models\MailDomain;
use App\Modules\EmailSecurity\Models\EmailSecurityPolicy;

class EmailSecurityService
{
    /**
     * Get or initialize security policy for an account or mail domain.
     */
    public function getOrCreatePolicy(HostingAccount $account, ?MailDomain $domain = null): EmailSecurityPolicy
    {
        return EmailSecurityPolicy::firstOrCreate(
            [
                'hosting_account_id' => $account->id,
                'mail_domain_id' => $domain?->id,
            ],
            [
                'spam_filter_enabled' => true,
                'greylisting_enabled' => true,
                'required_score' => 5.0,
                'reject_score' => 15.0,
                'auto_delete_spam' => false,
                'spam_subject_tag' => '***SPAM***',
                'allowlist' => ['*@trusted-partner.com'],
                'blocklist' => ['*@known-spammer.xyz'],
                'rbl_servers' => ['zen.spamhaus.org', 'bl.spamcop.net'],
            ]
        );
    }

    /**
     * Evaluate an email message against anti-spam heuristics and policy rules.
     *
     * @return array{
     *     score: float,
     *     is_spam: bool,
     *     action: string,
     *     triggered_rules: array<string>
     * }
     */
    public function evaluateMessage(string $sender, string $subject, string $body, EmailSecurityPolicy $policy): array
    {
        $sender = strtolower(trim($sender));
        $triggeredRules = [];

        // Check Allowlist
        foreach ($policy->allowlist ?? [] as $allowed) {
            if ($this->matchPattern($sender, strtolower($allowed))) {
                return [
                    'score' => -100.0,
                    'is_spam' => false,
                    'action' => 'accept',
                    'triggered_rules' => ['WHITELIST_SENDER: Matched ' . $allowed],
                ];
            }
        }

        // Check Blocklist
        foreach ($policy->blocklist ?? [] as $blocked) {
            if ($this->matchPattern($sender, strtolower($blocked))) {
                return [
                    'score' => 100.0,
                    'is_spam' => true,
                    'action' => 'reject',
                    'triggered_rules' => ['BLACKLIST_SENDER: Matched ' . $blocked],
                ];
            }
        }

        $score = 0.0;

        // Rule: All Caps Subject
        if (strlen($subject) > 8 && strtoupper($subject) === $subject) {
            $score += 2.8;
            $triggeredRules[] = 'SUBJ_ALL_CAPS: Subject line contains exclusively uppercase letters (+2.8)';
        }

        // Rule: Excessive Exclamation Marks
        if (substr_count($subject, '!') >= 3) {
            $score += 1.9;
            $triggeredRules[] = 'EXCESS_EXCLAMATIONS: 3 or more exclamation marks in subject (+1.9)';
        }

        // Rule: Suspicious Financial / Phishing Keywords
        $spamKeywords = [
            'VIAGRA' => 4.5,
            'CASINO' => 3.8,
            'FREE MONEY' => 4.2,
            'WIRE TRANSFER' => 3.5,
            'INHERITANCE' => 4.0,
            'CONGRATULATIONS WINNER' => 4.2,
            'CONFIRM YOUR PASSWORD' => 4.8,
            'CRYPTO GIVEAWAY' => 4.0,
        ];

        $combinedText = strtoupper($subject . ' ' . $body);
        foreach ($spamKeywords as $kw => $pts) {
            if (str_contains($combinedText, $kw)) {
                $score += $pts;
                $triggeredRules[] = "SPAM_KEYWORD_{$kw}: Detected phishing/spam token (+{$pts})";
            }
        }

        $score = round($score, 1);
        $isSpam = $score >= $policy->required_score;

        $action = 'accept';
        if ($score >= $policy->reject_score) {
            $action = 'reject';
        } elseif ($isSpam) {
            $action = $policy->auto_delete_spam ? 'discard' : 'tag';
        }

        return [
            'score' => $score,
            'is_spam' => $isSpam,
            'action' => $action,
            'triggered_rules' => $triggeredRules ?: ['CLEAN_MESSAGE: No suspicious indicators matched'],
        ];
    }

    /**
     * Add entry to allowlist.
     */
    public function addAllowlistEntry(EmailSecurityPolicy $policy, string $entry): void
    {
        $list = $policy->allowlist ?? [];
        $entry = trim($entry);
        if ($entry !== '' && !in_array($entry, $list, true)) {
            $list[] = $entry;
            $policy->allowlist = $list;
            $policy->save();
        }
    }

    /**
     * Remove entry from allowlist.
     */
    public function removeAllowlistEntry(EmailSecurityPolicy $policy, string $entry): void
    {
        $list = array_values(array_filter($policy->allowlist ?? [], fn($e) => $e !== $entry));
        $policy->allowlist = $list;
        $policy->save();
    }

    /**
     * Add entry to blocklist.
     */
    public function addBlocklistEntry(EmailSecurityPolicy $policy, string $entry): void
    {
        $list = $policy->blocklist ?? [];
        $entry = trim($entry);
        if ($entry !== '' && !in_array($entry, $list, true)) {
            $list[] = $entry;
            $policy->blocklist = $list;
            $policy->save();
        }
    }

    /**
     * Remove entry from blocklist.
     */
    public function removeBlocklistEntry(EmailSecurityPolicy $policy, string $entry): void
    {
        $list = array_values(array_filter($policy->blocklist ?? [], fn($e) => $e !== $entry));
        $policy->blocklist = $list;
        $policy->save();
    }

    /**
     * Helper to match wildcard strings (e.g. *@domain.com or user@domain.com).
     */
    protected function matchPattern(string $value, string $pattern): bool
    {
        if ($pattern === '*' || $pattern === $value) {
            return true;
        }

        $regex = '/^' . str_replace(['\*', '\?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';
        return (bool) preg_match($regex, $value);
    }
}
