<?php

declare(strict_types=1);

namespace App\Modules\DnsCluster\Models;

use App\Modules\Dns\Models\DnsZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsSyncLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'dns_cluster_node_id',
        'dns_zone_id',
        'event',
        'status',
        'records_count',
        'details',
        'created_at',
    ];

    protected $casts = [
        'records_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(DnsClusterNode::class, 'dns_cluster_node_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DnsZone::class, 'dns_zone_id');
    }
}
