<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Core\AbstractModule;

class IdentityModule extends AbstractModule
{
    protected string $id = 'identity';
    protected string $name = 'Identity & Access Control';
    protected string $version = '1.0.0';
    protected string $description = 'Authentication, Multi-Factor Authentication (TOTP), Roles and Permissions (RBAC), and session security.';
    protected int $priority = 2;
    protected array $dependencies = ['core'];
}
