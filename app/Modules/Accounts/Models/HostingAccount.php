<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Models;

use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HostingAccount extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PROVISIONING = 'provisioning';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'customer_id',
        'subscription_id',
        'server_id',
        'username',
        'primary_domain',
        'status',
        'uid',
        'gid',
        'homedir',
        'disk_used_mb',
        'bandwidth_used_mb',
        'suspended_reason',
    ];

    protected $casts = [
        'uid' => 'integer',
        'gid' => 'integer',
        'disk_used_mb' => 'integer',
        'bandwidth_used_mb' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function domains(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Domains\Models\Domain::class);
    }

    public function ftpAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Ftp\Models\FtpAccount::class);
    }

    public function cronJobs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Cron\Models\CronJob::class);
    }

    public function backups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Backups\Models\Backup::class);
    }

    public function backupSchedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Backups\Models\BackupSchedule::class);
    }

    public function wordPressInstances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\WordPressToolkit\Models\WordPressInstance::class);
    }

    public function gitRepositories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Git\Models\GitRepository::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
