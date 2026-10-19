<?php

declare(strict_types=1);

namespace App\Modules\Dns\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    use HasFactory;

    public const TYPE_A = 'A';
    public const TYPE_AAAA = 'AAAA';
    public const TYPE_CNAME = 'CNAME';
    public const TYPE_MX = 'MX';
    public const TYPE_TXT = 'TXT';
    public const TYPE_SRV = 'SRV';
    public const TYPE_CAA = 'CAA';
    public const TYPE_NS = 'NS';

    public const ALLOWED_TYPES = [
        self::TYPE_A,
        self::TYPE_AAAA,
        self::TYPE_CNAME,
        self::TYPE_MX,
        self::TYPE_TXT,
        self::TYPE_SRV,
        self::TYPE_CAA,
        self::TYPE_NS,
    ];

    protected $fillable = [
        'dns_zone_id',
        'name',
        'type',
        'content',
        'ttl',
        'priority',
        'weight',
        'port',
    ];

    protected $casts = [
        'ttl' => 'integer',
        'priority' => 'integer',
        'weight' => 'integer',
        'port' => 'integer',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DnsZone::class, 'dns_zone_id');
    }
}
