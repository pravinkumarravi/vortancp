<?php

declare(strict_types=1);

namespace App\Modules\Domains\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Domain extends Model
{
    use HasFactory;

    public const TYPE_PRIMARY = 'primary';
    public const TYPE_ADDON = 'addon';
    public const TYPE_SUBDOMAIN = 'subdomain';
    public const TYPE_ALIAS = 'alias';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PROVISIONING = 'provisioning';

    protected $fillable = [
        'hosting_account_id',
        'domain',
        'type',
        'parent_domain_id',
        'document_root',
        'php_version',
        'status',
        'force_https',
        'www_redirect',
    ];

    protected $casts = [
        'force_https' => 'boolean',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function parentDomain(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_domain_id');
    }

    public function subdomains(): HasMany
    {
        return $this->hasMany(self::class, 'parent_domain_id');
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(DomainRedirect::class);
    }

    public function virtualHost(): HasOne
    {
        return $this->hasOne(VirtualHost::class);
    }

    public function wordPressInstances(): HasMany
    {
        return $this->hasMany(\App\Modules\WordPressToolkit\Models\WordPressInstance::class);
    }

    public function gitRepositories(): HasMany
    {
        return $this->hasMany(\App\Modules\Git\Models\GitRepository::class);
    }

    public function installedApps(): HasMany
    {
        return $this->hasMany(\App\Modules\AppInstaller\Models\InstalledApp::class);
    }

    public function sslCertificates(): HasMany
    {
        return $this->hasMany(\App\Modules\Ssl\Models\SslCertificate::class);
    }

    public function activeSslCertificate(): HasOne
    {
        return $this->hasOne(\App\Modules\Ssl\Models\SslCertificate::class)
            ->where('status', \App\Modules\Ssl\Models\SslCertificate::STATUS_ACTIVE)
            ->latestOfMany();
    }

    public function trafficMetrics(): HasMany
    {
        return $this->hasMany(\App\Modules\Analytics\Models\DomainTrafficMetric::class);
    }

    public function isPrimary(): bool
    {
        return $this->type === self::TYPE_PRIMARY;
    }

    public function isSubdomain(): bool
    {
        return $this->type === self::TYPE_SUBDOMAIN;
    }

    public function isAlias(): bool
    {
        return $this->type === self::TYPE_ALIAS;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
