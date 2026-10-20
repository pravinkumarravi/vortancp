<?php

declare(strict_types=1);

namespace App\Modules\Billing;

use App\Modules\Core\AbstractModule;

class BillingModule extends AbstractModule
{
    protected string $id = 'billing';
    protected string $name = 'Billing & WHMCS Integration';
    protected string $version = '1.0.0';
    protected string $description = 'RESTful external billing API integration for WHMCS, Blesta, and HostBill with API key management, account provisioning, suspension, package changes, and termination.';
    protected int $priority = 26;
    protected array $dependencies = ['core', 'identity', 'accounts', 'hosting_plans', 'domains', 'servers'];
}
