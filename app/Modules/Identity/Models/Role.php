<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    public const SUPER_ADMIN = 'super_admin';
    public const SERVER_ADMIN = 'server_admin';
    public const RESELLER = 'reseller';
    public const CUSTOMER = 'customer';
    public const TEAM_MEMBER = 'team_member';
    public const READ_ONLY = 'read_only';

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    public function givePermissionTo(Permission|string $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::firstOrCreate(['name' => $permission], [
                'label' => ucwords(str_replace(['.', '_'], ' ', $permission)),
                'module' => explode('.', $permission)[0] ?? 'core',
            ]);
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
