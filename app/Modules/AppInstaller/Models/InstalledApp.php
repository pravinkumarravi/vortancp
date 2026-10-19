<?php

declare(strict_types=1);

namespace App\Modules\AppInstaller\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalledApp extends Model
{
    use HasFactory;

    protected $table = 'installed_apps';

    public const STATUS_INSTALLED = 'installed';
    public const STATUS_UPGRADING = 'upgrading';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'hosting_account_id',
        'domain_id',
        'app_slug',
        'app_name',
        'version',
        'install_path',
        'site_url',
        'db_name',
        'db_user',
        'admin_email',
        'status',
        'installed_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
