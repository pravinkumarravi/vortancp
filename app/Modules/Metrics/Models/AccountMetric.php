<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMetric extends Model
{
    use HasFactory;

    protected $table = 'account_metrics';

    protected $fillable = [
        'hosting_account_id',
        'disk_used_mb',
        'bandwidth_used_mb',
        'inodes_used',
        'recorded_at',
    ];

    protected $casts = [
        'disk_used_mb' => 'integer',
        'bandwidth_used_mb' => 'integer',
        'inodes_used' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}
