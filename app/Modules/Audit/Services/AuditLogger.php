<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    protected const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'secret',
        'two_factor_secret',
        'recovery_codes',
        'private_key',
        'key',
        'token',
        'api_token',
        'remember_token',
    ];

    /**
     * Record an audit event into the database.
     */
    public function log(
        string $event,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        $actorId = $userId ?? auth()->id();
        $ip = request()?->ip();
        $userAgent = request()?->userAgent();

        return AuditLog::create([
            'user_id' => $actorId,
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ? $this->sanitize($oldValues) : null,
            'new_values' => $newValues ? $this->sanitize($newValues) : null,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * Redact sensitive fields.
     */
    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_FIELDS, true)) {
                $data[$key] = '********';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
