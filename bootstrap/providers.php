<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    \App\Modules\Core\Providers\CoreServiceProvider::class,
    \App\Modules\Identity\Providers\IdentityServiceProvider::class,
    \App\Modules\Audit\Providers\AuditServiceProvider::class,
    \App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
    \App\Modules\Servers\Providers\ServersServiceProvider::class,
    \App\Modules\Accounts\Providers\AccountsServiceProvider::class,
    \App\Modules\Resellers\Providers\ResellersServiceProvider::class,
    \App\Modules\HostingPlans\Providers\HostingPlansServiceProvider::class,
    \App\Modules\Domains\Providers\DomainsServiceProvider::class,
    \App\Modules\Dns\Providers\DnsServiceProvider::class,
    \App\Modules\Ssl\Providers\SslServiceProvider::class,
    \App\Modules\FileManager\Providers\FileManagerServiceProvider::class,
    \App\Modules\Databases\Providers\DatabasesServiceProvider::class,
    \App\Modules\Email\Providers\EmailServiceProvider::class,
    \App\Modules\PhpManager\Providers\PhpManagerServiceProvider::class,
    \App\Modules\Ftp\Providers\FtpServiceProvider::class,
    \App\Modules\Cron\Providers\CronServiceProvider::class,
];
