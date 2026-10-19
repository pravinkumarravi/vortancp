<?php

declare(strict_types=1);

namespace App\Modules\Cron\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Cron\Models\CronJob;
use InvalidArgumentException;

class CrontabCompilerService
{
    /**
     * Forbidden command patterns to prevent elevation attacks.
     */
    protected const DISALLOWED_PATTERNS = [
        '/\bsudo\b/i',
        '/\bsu\b/i',
        '/\bdoas\b/i',
        '/\bchown\b/i',
        '/\bchmod\s+777\b/i',
        '/\brm\s+-rf\s+\/\b/i',
        '/\:\{\s*\:\s*\|\s*\:\s*&\s*\}\s*;\s*\:/', // fork bomb
    ];

    /**
     * Validate 5-part standard cron timing expression.
     */
    public function validateSchedule(
        string $minute,
        string $hour,
        string $dayOfMonth,
        string $month,
        string $dayOfWeek
    ): bool {
        $fields = [
            'minute' => [$minute, '/^(\*|[0-5]?\d)((\/|-|,)[0-5]?\d)*$/'],
            'hour' => [$hour, '/^(\*|(1?\d|2[0-3]))((\/|-|,)(1?\d|2[0-3]))*$/'],
            'dayOfMonth' => [$dayOfMonth, '/^(\*|([1-9]|[12]\d|3[01]))((\/|-|,)([1-9]|[12]\d|3[01]))*$/'],
            'month' => [$month, '/^(\*|([1-9]|1[0-2]))((\/|-|,)([1-9]|1[0-2]))*$/'],
            'dayOfWeek' => [$dayOfWeek, '/^(\*|[0-7])((\/|-|,)[0-7])*$/'],
        ];

        foreach ($fields as $fieldName => [$value, $regex]) {
            $val = trim($value);
            if ($val === '') {
                throw new InvalidArgumentException("Cron field '{$fieldName}' cannot be empty.");
            }
            if (! preg_match($regex, $val)) {
                throw new InvalidArgumentException("Invalid cron schedule expression for '{$fieldName}': '{$val}'");
            }
        }

        return true;
    }

    /**
     * Sanitize command line to prevent privilege escalation.
     */
    public function sanitizeCommand(string $command): string
    {
        $clean = trim($command);
        if ($clean === '') {
            throw new InvalidArgumentException("Cron command cannot be empty.");
        }

        if (str_contains($clean, "\0")) {
            throw new InvalidArgumentException("Null bytes detected in cron command.");
        }

        foreach (self::DISALLOWED_PATTERNS as $pattern) {
            if (preg_match($pattern, $clean)) {
                throw new InvalidArgumentException("Forbidden command pattern detected: privilege escalation commands are prohibited in cron.");
            }
        }

        return $clean;
    }

    /**
     * Compile standard RFC/Linux /var/spool/cron/crontabs/{username} syntax.
     */
    public function compileUserCrontab(HostingAccount $account, ?string $defaultMailto = null): string
    {
        $lines = [];
        $lines[] = '# ==============================================================================';
        $lines[] = '# VortanCP Automated Crontab Configuration';
        $lines[] = '# Account: ' . $account->username . ' (' . $account->primary_domain . ')';
        $lines[] = '# Generated at: ' . now()->toIso8601String();
        $lines[] = '# DO NOT EDIT DIRECTLY. Modifications will be overwritten by VortanCP.';
        $lines[] = '# ==============================================================================';
        $lines[] = 'SHELL=/bin/bash';
        $lines[] = 'PATH=/usr/local/bin:/usr/bin:/bin';
        $lines[] = 'HOME=' . ($account->homedir ?: "/home/{$account->username}");

        $mailto = $defaultMailto ?? '';
        $lines[] = 'MAILTO="' . addcslashes($mailto, '"') . '"';
        $lines[] = '';

        $cronJobs = $account->cronJobs()->where('is_active', true)->get();

        if ($cronJobs->isEmpty()) {
            $lines[] = '# (No active scheduled tasks configured)';
            return implode("\n", $lines) . "\n";
        }

        foreach ($cronJobs as $job) {
            if (! empty($job->description)) {
                $lines[] = '# ' . str_replace(["\r", "\n"], ' ', $job->description);
            }

            if (! empty($job->email_notification) && $job->email_notification !== $mailto) {
                $lines[] = 'MAILTO="' . addcslashes($job->email_notification, '"') . '"';
            }

            $schedule = sprintf(
                '%s %s %s %s %s',
                $job->minute,
                $job->hour,
                $job->day_of_month,
                $job->month,
                $job->day_of_week
            );

            $lines[] = "{$schedule} {$job->command}";

            if (! empty($job->email_notification) && $job->email_notification !== $mailto) {
                // Reset MAILTO back to default
                $lines[] = 'MAILTO="' . addcslashes($mailto, '"') . '"';
            }

            $lines[] = '';
        }

        return implode("\n", $lines) . "\n";
    }
}
