<?php

declare(strict_types=1);

namespace App\Modules\Cron\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronJob extends Model
{
    use HasFactory;

    protected $table = 'cron_jobs';

    protected $fillable = [
        'hosting_account_id',
        'minute',
        'hour',
        'day_of_month',
        'month',
        'day_of_week',
        'command',
        'description',
        'email_notification',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    /**
     * Get the formatted 5-part cron schedule expression.
     */
    public function getExpressionAttribute(): string
    {
        return sprintf(
            '%s %s %s %s %s',
            $this->minute ?: '*',
            $this->hour ?: '*',
            $this->day_of_month ?: '*',
            $this->month ?: '*',
            $this->day_of_week ?: '*'
        );
    }
}
