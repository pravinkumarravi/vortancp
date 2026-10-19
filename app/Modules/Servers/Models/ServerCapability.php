<?php

declare(strict_types=1);

namespace App\Modules\Servers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCapability extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'capability',
        'version',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
