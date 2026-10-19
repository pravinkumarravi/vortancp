<?php

declare(strict_types=1);

namespace App\Modules\Accounts;

use App\Modules\Core\AbstractModule;

class AccountsModule extends AbstractModule
{
    protected string $id = 'accounts';
    protected string $name = 'Customer & Account Management';
    protected string $version = '1.0.0';
    protected string $description = 'Manage customer profiles, hosting sandboxes, Linux system users, quotas, and account suspensions.';
    protected int $priority = 6;
    protected array $dependencies = ['core', 'identity', 'servers'];
}
