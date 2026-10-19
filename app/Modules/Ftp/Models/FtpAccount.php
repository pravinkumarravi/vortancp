<?php

declare(strict_types=1);

namespace App\Modules\Ftp\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtpAccount extends Model
{
    use HasFactory;

    protected $table = 'ftp_accounts';

    protected $fillable = [
        'hosting_account_id',
        'username',
        'password_hash',
        'homedir',
        'quota_mb',
        'upload_bandwidth_kb',
        'download_bandwidth_kb',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quota_mb' => 'integer',
        'upload_bandwidth_kb' => 'integer',
        'download_bandwidth_kb' => 'integer',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}
