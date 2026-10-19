<?php

declare(strict_types=1);

namespace App\Modules\Email\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailDomain extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'domain_id',
        'hosting_account_id',
        'name',
        'dkim_selector',
        'dkim_private_key',
        'dkim_public_key',
        'status',
    ];

    protected $hidden = [
        'dkim_private_key',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(MailAlias::class);
    }
}
