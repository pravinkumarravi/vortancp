<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarantinedFile extends Model
{
    use HasFactory;

    protected $table = 'quarantined_files';

    public const STATUS_QUARANTINED = 'quarantined';
    public const STATUS_RESTORED = 'restored';
    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'security_scan_id',
        'original_path',
        'quarantine_path',
        'threat_name',
        'status',
        'quarantined_at',
    ];

    protected $casts = [
        'quarantined_at' => 'datetime',
    ];

    public function securityScan(): BelongsTo
    {
        return $this->belongsTo(SecurityScan::class);
    }
}
