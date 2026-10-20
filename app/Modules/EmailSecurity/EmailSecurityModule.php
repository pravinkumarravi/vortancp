<?php

declare(strict_types=1);

namespace App\Modules\EmailSecurity;

use App\Modules\Core\AbstractModule;

class EmailSecurityModule extends AbstractModule
{
    protected string $id = 'email_security';
    protected string $name = 'Email Anti-Spam & Security';
    protected string $version = '1.0.0';
    protected string $description = 'Rspamd and SpamAssassin mail filtering, automated greylisting, real-time blackhole list (RBL) defense, and customizable sender allowlists/blocklists.';
    protected int $priority = 33;
    protected array $dependencies = ['core', 'identity', 'servers', 'email', 'accounts'];
}
