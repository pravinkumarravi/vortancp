<?php

declare(strict_types=1);

namespace App\Modules\Email;

use App\Modules\Core\AbstractModule;

class EmailModule extends AbstractModule
{
    protected string $id = 'email';
    protected string $name = 'Email & Mailboxes';
    protected string $version = '1.0.0';
    protected string $description = 'Postfix virtual mailbox maps, Dovecot storage quotas, automated DKIM key generation, and webmail integrations.';
    protected int $priority = 15;
    protected array $dependencies = ['core', 'identity', 'servers', 'accounts', 'domains', 'hosting_plans'];
}
