<?php

declare(strict_types=1);

namespace App\Modules\Servers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_OFFLINE = 'offline';

    public const CONNECTION_LOCAL = 'local_socket';
    public const CONNECTION_REMOTE = 'remote_mtls';

    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'port',
        'connection_type',
        'auth_token',
        'status',
        'os_name',
        'os_version',
        'kernel_version',
        'cpu_cores',
        'total_ram_mb',
        'used_ram_mb',
        'total_disk_gb',
        'used_disk_gb',
        'load_average',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'port' => 'integer',
        'cpu_cores' => 'integer',
        'total_ram_mb' => 'integer',
        'used_ram_mb' => 'integer',
        'total_disk_gb' => 'integer',
        'used_disk_gb' => 'integer',
        'load_average' => 'float',
        'auth_token' => 'encrypted',
        'last_heartbeat_at' => 'datetime',
    ];

    public function capabilities(): HasMany
    {
        return $this->hasMany(ServerCapability::class);
    }

    public function firewallRules(): HasMany
    {
        return $this->hasMany(\App\Modules\Firewall\Models\FirewallRule::class);
    }

    public function bannedIps(): HasMany
    {
        return $this->hasMany(\App\Modules\Firewall\Models\BannedIp::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocal(): bool
    {
        return $this->connection_type === self::CONNECTION_LOCAL;
    }

    public function isHealthy(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Heartbeat considered healthy if reported within last 5 minutes
        if ($this->last_heartbeat_at === null || $this->last_heartbeat_at->diffInMinutes(now()) > 5) {
            return false;
        }

        return true;
    }

    public function getRamUsagePercentage(): float
    {
        if ($this->total_ram_mb <= 0) {
            return 0.0;
        }

        return round(($this->used_ram_mb / $this->total_ram_mb) * 100, 1);
    }

    public function getDiskUsagePercentage(): float
    {
        if ($this->total_disk_gb <= 0) {
            return 0.0;
        }

        return round(($this->used_disk_gb / $this->total_disk_gb) * 100, 1);
    }

    public function hasCapability(string $capability): bool
    {
        return $this->capabilities()
            ->where('capability', $capability)
            ->where('is_active', true)
            ->exists();
    }
}
