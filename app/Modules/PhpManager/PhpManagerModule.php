<?php

declare(strict_types=1);

namespace App\Modules\PhpManager;

use App\Modules\Core\AbstractModule;

class PhpManagerModule extends AbstractModule
{
    protected string $id = 'phpmanager';
    protected string $name = 'PHP Multi-Version Manager';
    protected string $version = '1.0.0';
    protected string $description = 'Multi-version PHP runtime management, isolated per-user PHP-FPM pools, INI directives editor, and domain engine switching.';
    protected int $priority = 16;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'domains'];
}
