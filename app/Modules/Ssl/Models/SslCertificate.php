<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'domain_id',
        'hosting_account_id',
        'issuer',
        'subject_dn',
        'sans',
        'certificate',
        'private_key',
        'chain',
        'valid_from',
        'valid_until',
        'auto_renew',
        'status',
    ];

    protected $casts = [
        'sans' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    protected $hidden = [
        'private_key',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast();
    }

    public function daysRemaining(): int
    {
        return (int) max(0, now()->diffInDays($this->valid_until, false));
    }

    public function needsRenewal(): bool
    {
        return $this->auto_renew && $this->daysRemaining() <= 30;
    }
}
