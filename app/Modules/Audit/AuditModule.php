<?php

declare(strict_types=1);

namespace App\Modules\Audit;

use App\Modules\Core\AbstractModule;

class AuditModule extends AbstractModule
{
    protected string $id = 'audit';
    protected string $name = 'Audit Logging';
    protected string $version = '1.0.0';
    protected string $description = 'Immutable system audit logging, security event records, and administrative action traces.';
    protected int $priority = 3;
    protected array $dependencies = ['core', 'identity'];
}
