<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Modules\Core\AbstractModule;

class DashboardModule extends AbstractModule
{
    protected string $id = 'dashboard';
    protected string $name = 'Dashboard & Overview';
    protected string $version = '1.0.0';
    protected string $description = 'Role-based operational dashboards for Administrators, Resellers, and Hosting Customers.';
    protected int $priority = 4;
    protected array $dependencies = ['core', 'identity'];
}
