<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Models;

use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    use HasFactory;

    protected $table = 'server_metrics';

    protected $fillable = [
        'server_id',
        'cpu_pct',
        'memory_pct',
        'memory_used_mb',
        'memory_total_mb',
        'disk_pct',
        'disk_used_mb',
        'disk_total_mb',
        'load_1min',
        'recorded_at',
    ];

    protected $casts = [
        'cpu_pct' => 'float',
        'memory_pct' => 'float',
        'disk_pct' => 'float',
        'load_1min' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
