<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'key_prefix',
        'key_hash',
        'abilities',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'key_hash',
    ];

    /**
     * Generate a new cryptographically secure API key.
     *
     * @param string $name
     * @param array<int, string> $abilities
     * @param CarbonInterface|null $expiresAt
     * @return array{apiKey: self, plainTextToken: string}
     */
    public static function generate(string $name, array $abilities = ['*'], ?CarbonInterface $expiresAt = null): array
    {
        $rawSecret = 'vortan_' . Str::random(40);
        $prefix = substr($rawSecret, 0, 15);
        $hash = hash('sha256', $rawSecret);

        $apiKey = static::create([
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => $hash,
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return [
            'apiKey' => $apiKey,
            'plainTextToken' => $rawSecret,
        ];
    }

    /**
     * Validate a plain text API token against stored hash.
     */
    public static function validateToken(string $plainTextToken): ?self
    {
        $hash = hash('sha256', trim($plainTextToken));

        /** @var self|null $apiKey */
        $apiKey = static::where('key_hash', $hash)
            ->where('is_active', true)
            ->first();

        if ($apiKey === null) {
            return null;
        }

        if ($apiKey->expires_at !== null && $apiKey->expires_at->isPast()) {
            return null;
        }

        $apiKey->update(['last_used_at' => now()]);

        return $apiKey;
    }

    /**
     * Check whether this API key has a specific ability.
     */
    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? ['*'];

        if (in_array('*', $abilities, true)) {
            return true;
        }

        return in_array($ability, $abilities, true);
    }
}
