<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityScan extends Model
{
    use HasFactory;

    protected $table = 'security_scans';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_INFECTED = 'infected';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'server_id',
        'hosting_account_id',
        'target_path',
        'status',
        'scanned_files_count',
        'infected_files_count',
        'results',
        'completed_at',
    ];

    protected $casts = [
        'scanned_files_count' => 'integer',
        'infected_files_count' => 'integer',
        'results' => 'array',
        'completed_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function quarantinedFiles(): HasMany
    {
        return $this->hasMany(QuarantinedFile::class);
    }
}
