<?php

declare(strict_types=1);

namespace App\Modules\Email\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_domain_id',
        'source_email',
        'destination_email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mailDomain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class);
    }
}
