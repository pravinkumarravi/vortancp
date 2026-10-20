<?php

declare(strict_types=1);

namespace App\Modules\Terminal;

use App\Modules\Core\AbstractModule;

class TerminalModule extends AbstractModule
{
    protected string $id = 'terminal';
    protected string $name = 'In-Browser Web Terminal';
    protected string $version = '1.0.0';
    protected string $description = 'Interactive in-browser web terminal console with jailed customer shell environments, command sanitization, privilege barriers, and audit logging.';
    protected int $priority = 29;
    protected array $dependencies = ['core', 'identity', 'servers', 'hosting_plans', 'audit'];
}
