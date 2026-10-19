<?php

declare(strict_types=1);

namespace App\Modules\Identity\Traits;

use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\SessionHistory;
use App\Modules\Identity\Models\TwoFactorCredential;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function twoFactor(): HasOne
    {
        return $this->hasOne(TwoFactorCredential::class);
    }

    public function sessionHistories(): HasMany
    {
        return $this->hasMany(SessionHistory::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions implicitly
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Direct or role-based permission lookup
        return $this->roles
            ->loadMissing('permissions')
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->contains($permission);
    }

    public function assignRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::firstOrCreate(['name' => $role], [
                'label' => ucwords(str_replace('_', ' ', $role)),
            ]);
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->load('roles');
    }

    public function removeRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
            $this->load('roles');
        }
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function isServerAdmin(): bool
    {
        return $this->hasRole([Role::SUPER_ADMIN, Role::SERVER_ADMIN]);
    }

    public function isReseller(): bool
    {
        return $this->hasRole(Role::RESELLER);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(Role::CUSTOMER);
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactor?->is_enabled === true;
    }

    /**
     * Helper to return hosting account IDs accessible by this user.
     *
     * @return array<int>
     */
    public function accessibleAccountIds(): array
    {
        if ($this->isSuperAdmin() || $this->isServerAdmin()) {
            return []; // Empty indicates unrestricted global access
        }

        // Will be wired to Accounts/Resellers modules in Phase 2
        return [];
    }
}
