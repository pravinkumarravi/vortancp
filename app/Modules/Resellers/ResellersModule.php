<?php

declare(strict_types=1);

namespace App\Modules\Resellers;

use App\Modules\Core\AbstractModule;

class ResellersModule extends AbstractModule
{
    protected string $id = 'resellers';
    protected string $name = 'Reseller Management';
    protected string $version = '1.0.0';
    protected string $description = 'Reseller resource allocation pools, reseller accounts, custom packages, and tenant hierarchy validation.';
    protected int $priority = 7;
    protected array $dependencies = ['core', 'identity', 'accounts'];
}
