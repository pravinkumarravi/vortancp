<?php

declare(strict_types=1);

namespace App\Modules\WordPressToolkit\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordPressInstance extends Model
{
    use HasFactory;

    protected $table = 'wordpress_instances';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_NEEDS_UPDATE = 'needs_update';

    protected $fillable = [
        'hosting_account_id',
        'domain_id',
        'site_title',
        'site_url',
        'path',
        'db_name',
        'db_user',
        'wp_version',
        'admin_email',
        'status',
        'auto_update_core',
        'auto_update_plugins',
        'xmlrpc_blocked',
        'file_editing_disabled',
        'table_prefix',
        'last_scanned_at',
    ];

    protected $casts = [
        'auto_update_core' => 'boolean',
        'auto_update_plugins' => 'boolean',
        'xmlrpc_blocked' => 'boolean',
        'file_editing_disabled' => 'boolean',
        'last_scanned_at' => 'datetime',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function isMaintenance(): bool
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }
}
