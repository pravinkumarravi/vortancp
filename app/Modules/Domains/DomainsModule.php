<?php

declare(strict_types=1);

namespace App\Modules\Domains;

use App\Modules\Core\AbstractModule;

class DomainsModule extends AbstractModule
{
    protected string $id = 'domains';
    protected string $name = 'Domains & Websites';
    protected string $version = '1.0.0';
    protected string $description = 'Virtual host orchestration, document roots, multi-PHP engine assignment, domain redirects, and vhost configuration synthesis.';
    protected int $priority = 10;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'hosting_plans'];
}
