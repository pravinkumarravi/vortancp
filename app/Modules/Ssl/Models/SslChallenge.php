<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Models;

use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'token',
        'payload',
        'type',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
