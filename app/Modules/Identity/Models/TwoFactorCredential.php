<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'secret',
        'is_enabled',
        'recovery_codes',
        'confirmed_at',
    ];

    protected $casts = [
        'secret' => 'encrypted',
        'is_enabled' => 'boolean',
        'recovery_codes' => 'encrypted:array',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
