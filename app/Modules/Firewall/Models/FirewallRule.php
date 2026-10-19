<?php

declare(strict_types=1);

namespace App\Modules\Firewall\Models;

use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirewallRule extends Model
{
    use HasFactory;

    protected $table = 'firewall_rules';

    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    public const ACTION_ALLOW = 'allow';
    public const ACTION_DENY = 'deny';
    public const ACTION_REJECT = 'reject';
    public const ACTION_LIMIT = 'limit';

    public const PROTO_TCP = 'tcp';
    public const PROTO_UDP = 'udp';
    public const PROTO_ANY = 'any';

    protected $fillable = [
        'server_id',
        'direction',
        'action',
        'protocol',
        'port',
        'source_ip',
        'comment',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
