<?php

declare(strict_types=1);

namespace App\Modules\Backups\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    protected $table = 'backups';

    public const TYPE_FULL = 'full';
    public const TYPE_FILES_ONLY = 'files_only';
    public const TYPE_DATABASE_ONLY = 'database_only';
    public const TYPE_EMAIL_ONLY = 'email_only';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'hosting_account_id',
        'filename',
        'type',
        'destination',
        'size_bytes',
        'status',
        'checksum_sha256',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes ?? 0;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
