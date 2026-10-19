<?php

declare(strict_types=1);

namespace App\Modules\Domains\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRedirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'path',
        'target_url',
        'status_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_code' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
