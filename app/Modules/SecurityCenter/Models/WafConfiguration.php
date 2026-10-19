<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Models;

use App\Modules\Domains\Models\Domain;
use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WafConfiguration extends Model
{
    use HasFactory;

    protected $table = 'waf_configurations';

    protected $fillable = [
        'server_id',
        'domain_id',
        'is_enabled',
        'paranoia_level',
        'block_sqli',
        'block_xss',
        'block_rfi',
        'block_code_injection',
        'anomaly_threshold',
        'custom_rules',
        'disabled_rule_ids',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'paranoia_level' => 'integer',
        'block_sqli' => 'boolean',
        'block_xss' => 'boolean',
        'block_rfi' => 'boolean',
        'block_code_injection' => 'boolean',
        'anomaly_threshold' => 'integer',
        'disabled_rule_ids' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
