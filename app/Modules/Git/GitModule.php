<?php

declare(strict_types=1);

namespace App\Modules\Git;

use App\Modules\Core\AbstractModule;

class GitModule extends AbstractModule
{
    protected string $id = 'git';
    protected string $name = 'Git Repository Manager';
    protected string $version = '1.0.0';
    protected string $description = 'Git version control management, SSH deploy keys, remote tracking, and webhook automated deployment pipelines.';
    protected int $priority = 22;
    protected array $dependencies = ['core', 'identity', 'domains', 'hosting_plans'];
}
