<?php

declare(strict_types=1);

namespace App\Modules\AppInstaller;

use App\Modules\Core\AbstractModule;

class AppInstallerModule extends AbstractModule
{
    protected string $id = 'app_installer';
    protected string $name = '1-Click Application Installer';
    protected string $version = '1.0.0';
    protected string $description = '1-click deployment and provisioning for web applications including Laravel, phpMyAdmin, Roundcube, Nextcloud, Drupal, and Joomla.';
    protected int $priority = 26;
    protected array $dependencies = ['core', 'identity', 'domains', 'databases', 'hosting_plans'];
}
