<?php

declare(strict_types=1);

namespace App\Modules\Resellers\Models;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\HostingPlans\Models\HostingPlan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reseller extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'name',
        'status',
        'max_customers',
        'max_disk_mb',
        'max_bandwidth_mb',
        'max_domains',
    ];

    protected $casts = [
        'max_customers' => 'integer',
        'max_disk_mb' => 'integer',
        'max_bandwidth_mb' => 'integer',
        'max_domains' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function hostingPlans(): HasMany
    {
        return $this->hasMany(HostingPlan::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
