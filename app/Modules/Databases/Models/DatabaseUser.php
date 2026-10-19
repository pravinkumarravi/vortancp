<?php

declare(strict_types=1);

namespace App\Modules\Databases\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Servers\Models\Server;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseUser extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'hosting_account_id',
        'server_id',
        'username',
        'host',
        'password_hash',
        'status',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DatabaseUserAssignment::class);
    }

    public function databases(): BelongsToMany
    {
        return $this->belongsToMany(Database::class, 'database_user_assignments')
            ->withPivot('privileges')
            ->withTimestamps();
    }
}
