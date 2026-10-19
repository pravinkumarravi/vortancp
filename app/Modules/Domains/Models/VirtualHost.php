<?php

declare(strict_types=1);

namespace App\Modules\Domains\Models;

use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualHost extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'server_id',
        'webserver_type',
        'config_path',
        'is_ssl_enabled',
        'last_compiled_at',
    ];

    protected $casts = [
        'is_ssl_enabled' => 'boolean',
        'last_compiled_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
