<?php

declare(strict_types=1);

namespace App\Modules\HostingPlans\Models;

use App\Modules\Resellers\Models\Reseller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'name',
        'slug',
        'description',
        'is_active',
        'max_domains',
        'max_subdomains',
        'max_disk_mb',
        'max_bandwidth_mb',
        'max_databases',
        'max_mailboxes',
        'max_ftp_accounts',
        'max_cron_jobs',
        'has_ssh_access',
        'has_ssl',
        'has_git',
        'has_dns_management',
        'has_wordpress_toolkit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_domains' => 'integer',
        'max_subdomains' => 'integer',
        'max_disk_mb' => 'integer',
        'max_bandwidth_mb' => 'integer',
        'max_databases' => 'integer',
        'max_mailboxes' => 'integer',
        'max_ftp_accounts' => 'integer',
        'max_cron_jobs' => 'integer',
        'has_ssh_access' => 'boolean',
        'has_ssl' => 'boolean',
        'has_git' => 'boolean',
        'has_dns_management' => 'boolean',
        'has_wordpress_toolkit' => 'boolean',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isSystemPlan(): bool
    {
        return $this->reseller_id === null;
    }
}
