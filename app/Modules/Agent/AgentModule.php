<?php

declare(strict_types=1);

namespace App\Modules\Agent;

use App\Modules\Core\AbstractModule;

class AgentModule extends AbstractModule
{
    protected string $id = 'agent';
    protected string $name = 'Vortan Agent Daemon & IPC';
    protected string $version = '1.0.0';
    protected string $description = 'Privileged host orchestration daemon, allowlisted JSON-RPC 2.0 protocol, HMAC verification, and secure systemd/chroot/Nginx execution.';
    protected int $priority = 10;
    protected array $dependencies = ['core', 'servers'];
}
