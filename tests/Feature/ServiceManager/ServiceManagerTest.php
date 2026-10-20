<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceManager;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\ServiceManager\Livewire\ServiceManagerIndex;
use App\Modules\ServiceManager\Models\MonitoredService;
use App\Modules\ServiceManager\Services\ServiceManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        MonitoredService::seedDefaults();
    }

    public function test_admin_can_view_service_manager_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/services');

        $response->assertOk()
            ->assertSee('Service Manager')
            ->assertSee('Nginx HTTP / Reverse Proxy Server')
            ->assertSee('MariaDB SQL Database Server')
            ->assertSee('BIND9 DNS Authoritative Nameserver');
    }

    public function test_customer_is_forbidden_from_service_manager(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/admin/services');

        $response->assertForbidden();
    }

    public function test_can_restart_service_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(ServiceManagerIndex::class)
            ->call('restart', 'nginx')
            ->assertSee('Nginx HTTP / Reverse Proxy Server');

        $service = MonitoredService::where('name', 'nginx')->first();
        $this->assertEquals(MonitoredService::STATUS_RUNNING, $service->status);
        $this->assertEquals(1, $service->restart_count);
        $this->assertNotNull($service->last_restarted_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'service.controlled',
        ]);
    }

    public function test_can_reload_service_via_livewire(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(ServiceManagerIndex::class)
            ->call('reload', 'php8.3-fpm')
            ->assertSee('PHP 8.3 FastCGI Process Manager');

        $service = MonitoredService::where('name', 'php8.3-fpm')->first();
        $this->assertEquals(MonitoredService::STATUS_RUNNING, $service->status);
        $this->assertEquals(1, $service->restart_count);
    }

    public function test_can_stop_and_start_service_via_livewire(): void
    {
        // Stop service
        Livewire::actingAs($this->adminUser)
            ->test(ServiceManagerIndex::class)
            ->call('stop', 'pure-ftpd');

        $service = MonitoredService::where('name', 'pure-ftpd')->first();
        $this->assertEquals(MonitoredService::STATUS_STOPPED, $service->status);

        // Start service
        Livewire::actingAs($this->adminUser)
            ->test(ServiceManagerIndex::class)
            ->call('start', 'pure-ftpd');

        $this->assertEquals(MonitoredService::STATUS_RUNNING, $service->fresh()->status);
    }

    public function test_can_toggle_monitoring_and_auto_restart(): void
    {
        $service = MonitoredService::where('name', 'mariadb')->first();
        $this->assertTrue($service->is_monitored);
        $this->assertTrue($service->auto_restart);

        // Toggle monitoring off
        Livewire::actingAs($this->adminUser)
            ->test(ServiceManagerIndex::class)
            ->call('toggleMonitoring', $service->id);

        $this->assertFalse($service->fresh()->is_monitored);

        // Toggle auto-restart off
        Livewire::actingAs($this->adminUser)
            ->test(ServiceManagerIndex::class)
            ->call('toggleAutoRestart', $service->id);

        $this->assertFalse($service->fresh()->auto_restart);
    }

    public function test_watchdog_auto_heals_stopped_or_failed_service(): void
    {
        $service = MonitoredService::where('name', 'named')->first();
        $service->update(['status' => MonitoredService::STATUS_STOPPED]);

        Livewire::actingAs($this->adminUser)
            ->test(ServiceManagerIndex::class)
            ->call('triggerWatchdog')
            ->assertSee('Auto-healed');

        $this->assertEquals(MonitoredService::STATUS_RUNNING, $service->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'service.watchdog_auto_healed',
        ]);
    }

    public function test_watchdog_artisan_command_executes_successfully(): void
    {
        $this->artisan('vortan:services-watchdog')
            ->expectsOutputToContain('Executing VortanCP Service Watchdog check')
            ->assertExitCode(0);
    }
}
