<?php

declare(strict_types=1);

namespace App\Modules\PhpManager\Models;

use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhpInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'version',
        'bin_path',
        'fpm_service',
        'status',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
