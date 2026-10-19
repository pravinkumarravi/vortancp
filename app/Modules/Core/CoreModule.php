<?php

declare(strict_types=1);

namespace App\Modules\Core;

class CoreModule extends AbstractModule
{
    protected string $id = 'core';
    protected string $name = 'Core Platform';
    protected string $version = '1.0.0';
    protected string $description = 'Core platform infrastructure, module registry, and navigation engine.';
    protected int $priority = 1;
    protected array $dependencies = [];
}
