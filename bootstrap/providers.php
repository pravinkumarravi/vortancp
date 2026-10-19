<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    \App\Modules\Core\Providers\CoreServiceProvider::class,
    \App\Modules\Identity\Providers\IdentityServiceProvider::class,
    \App\Modules\Audit\Providers\AuditServiceProvider::class,
    \App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
];
