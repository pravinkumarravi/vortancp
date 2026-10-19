<?php

declare(strict_types=1);

namespace App\Modules\Servers\Database\Seeders;

use App\Modules\Servers\Services\ServerNodeService;
use Illuminate\Database\Seeder;

class ServerDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $serverService = app(ServerNodeService::class);

        $serverService->registerServer([
            'name' => 'Vortan Master Node 01',
            'hostname' => 'node01.vortan.local',
            'ip_address' => '127.0.0.1',
            'port' => 8443,
            'connection_type' => 'local_socket',
            'status' => 'active',
            'os_name' => 'Linux',
            'os_version' => 'Ubuntu 24.04.1 LTS',
            'cpu_cores' => 8,
            'total_ram_mb' => 16384,
            'total_disk_gb' => 500,
        ]);
    }
}
