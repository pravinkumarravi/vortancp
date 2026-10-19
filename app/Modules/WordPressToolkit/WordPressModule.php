<?php

declare(strict_types=1);

namespace App\Modules\WordPressToolkit;

use App\Modules\Core\AbstractModule;

class WordPressModule extends AbstractModule
{
    protected string $id = 'wordpress_toolkit';
    protected string $name = 'WordPress Toolkit';
    protected string $version = '1.0.0';
    protected string $description = 'Centralized WordPress instance management, 1-click installation, auto-updates, security hardening, and SSO login.';
    protected int $priority = 25;
    protected array $dependencies = ['core', 'identity', 'domains', 'databases', 'hosting_plans'];
}
