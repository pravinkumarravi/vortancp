<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainTrafficMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'hosting_account_id',
        'date',
        'total_requests',
        'unique_visitors',
        'bandwidth_bytes',
        'status_2xx',
        'status_3xx',
        'status_4xx',
        'status_5xx',
        'top_paths',
        'top_referrers',
        'top_user_agents',
    ];

    protected $casts = [
        'date' => 'string',
        'total_requests' => 'integer',
        'unique_visitors' => 'integer',
        'bandwidth_bytes' => 'integer',
        'status_2xx' => 'integer',
        'status_3xx' => 'integer',
        'status_4xx' => 'integer',
        'status_5xx' => 'integer',
        'top_paths' => 'array',
        'top_referrers' => 'array',
        'top_user_agents' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function getFormattedBandwidthAttribute(): string
    {
        $bytes = $this->bandwidth_bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }
}
