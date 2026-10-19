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
];
