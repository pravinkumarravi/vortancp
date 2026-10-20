<?php

declare(strict_types=1);

namespace App\Modules\EmailSecurity\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Email\Models\MailDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSecurityPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosting_account_id',
        'mail_domain_id',
        'spam_filter_enabled',
        'greylisting_enabled',
        'required_score',
        'reject_score',
        'auto_delete_spam',
        'spam_subject_tag',
        'allowlist',
        'blocklist',
        'rbl_servers',
    ];

    protected $casts = [
        'spam_filter_enabled' => 'boolean',
        'greylisting_enabled' => 'boolean',
        'required_score' => 'float',
        'reject_score' => 'float',
        'auto_delete_spam' => 'boolean',
        'allowlist' => 'array',
        'blocklist' => 'array',
        'rbl_servers' => 'array',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function mailDomain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class);
    }
}
