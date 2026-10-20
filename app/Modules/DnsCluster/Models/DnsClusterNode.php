<?php

declare(strict_types=1);

namespace App\Modules\DnsCluster\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DnsClusterNode extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_SYNCING = 'syncing';
    public const STATUS_ERROR = 'error';

    public const SYNC_MASTER = 'master';
    public const SYNC_SLAVE = 'slave';
    public const SYNC_WRITE_ONLY = 'write_only';
    public const SYNC_STANDALONE = 'standalone';

    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'api_key_hash',
        'sync_type',
        'status',
        'zones_count',
        'latency_ms',
        'last_ping_at',
        'last_synced_at',
        'error_message',
    ];

    protected $casts = [
        'zones_count' => 'integer',
        'latency_ms' => 'float',
        'last_ping_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function syncLogs(): HasMany
    {
        return $this->hasMany(DnsSyncLog::class);
    }
}
