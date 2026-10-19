<?php

declare(strict_types=1);

namespace App\Modules\Email\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mailbox extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'mail_domain_id',
        'hosting_account_id',
        'email',
        'local_part',
        'password_hash',
        'quota_mb',
        'used_mb',
        'status',
    ];

    protected $casts = [
        'quota_mb' => 'integer',
        'used_mb' => 'integer',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function mailDomain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class);
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function usagePercent(): int
    {
        if ($this->quota_mb <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->used_mb / $this->quota_mb) * 100));
    }
}
