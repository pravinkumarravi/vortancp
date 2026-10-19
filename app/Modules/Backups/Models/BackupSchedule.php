<?php

declare(strict_types=1);

namespace App\Modules\Backups\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupSchedule extends Model
{
    use HasFactory;

    protected $table = 'backup_schedules';

    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_MONTHLY = 'monthly';

    protected $fillable = [
        'hosting_account_id',
        'frequency',
        'retention_count',
        'destination',
        'include_files',
        'include_databases',
        'include_emails',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'retention_count' => 'integer',
        'include_files' => 'boolean',
        'include_databases' => 'boolean',
        'include_emails' => 'boolean',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}
