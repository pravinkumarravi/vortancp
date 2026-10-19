<?php

declare(strict_types=1);

namespace App\Modules\HostingPlans;

use App\Modules\Core\AbstractModule;

class HostingPlansModule extends AbstractModule
{
    protected string $id = 'hosting_plans';
    protected string $name = 'Hosting Plans & Subscriptions';
    protected string $version = '1.0.0';
    protected string $description = 'Hosting service packages, client subscriptions, add-on plans, and centralized entitlement quotas.';
    protected int $priority = 8;
    protected array $dependencies = ['core', 'identity', 'accounts'];
}
