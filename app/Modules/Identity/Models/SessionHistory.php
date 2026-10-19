<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'is_revoked',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
