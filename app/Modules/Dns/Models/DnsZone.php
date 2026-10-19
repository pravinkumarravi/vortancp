<?php

declare(strict_types=1);

namespace App\Modules\Dns\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DnsZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'hosting_account_id',
        'name',
        'status',
        'serial',
        'primary_ns',
        'admin_email',
        'refresh_sec',
        'retry_sec',
        'expire_sec',
        'minimum_ttl',
    ];

    protected $casts = [
        'serial' => 'integer',
        'refresh_sec' => 'integer',
        'retry_sec' => 'integer',
        'expire_sec' => 'integer',
        'minimum_ttl' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    /**
     * Increment the zone serial following YYYYMMDDnn format.
     */
    public function incrementSerial(): void
    {
        $todayPrefix = (int) date('Ymd00');
        if ($this->serial < $todayPrefix) {
            $this->serial = $todayPrefix + 1;
        } else {
            $this->serial++;
        }
        $this->save();
    }
}
