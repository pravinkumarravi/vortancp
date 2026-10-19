<?php

declare(strict_types=1);

namespace App\Modules\Firewall\Models;

use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannedIp extends Model
{
    use HasFactory;

    protected $table = 'banned_ips';

    protected $fillable = [
        'server_id',
        'ip_address',
        'jail',
        'failures_count',
        'reason',
        'banned_at',
        'expires_at',
    ];

    protected $casts = [
        'failures_count' => 'integer',
        'banned_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
