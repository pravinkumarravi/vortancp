<?php

declare(strict_types=1);

namespace Tests\Feature\Dns;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Dns\Livewire\DnsZoneEditor;
use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Dns\Services\DnsZoneService;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DnsManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private HostingAccount $account;
    private Domain $domain;
    private DnsZone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'DNS Master',
            'hostname' => 'ns1.vortan.local',
            'ip_address' => '192.168.10.50',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Acme Web Client',
            'email' => 'customer@vortan.local',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'DNS Enabled Plan',
            'slug' => 'dns-plan',
            'disk_quota_mb' => 5000,
            'bandwidth_quota_mb' => 50000,
            'max_domains' => 5,
            'max_subdomains' => 5,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
            'has_ssh_access' => true,
            'has_ssl' => true,
            'has_git' => true,
            'has_dns_management' => true,
            'has_wordpress_toolkit' => true,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $server->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => 'acmedns',
            'primary_domain' => 'mybrand.org',
            'status' => 'active',
            'uid' => 1004,
            'gid' => 1004,
            'homedir' => '/home/acmedns',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'mybrand.org',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/home/acmedns/public_html',
            'status' => 'active',
        ]);

        $dnsService = app(DnsZoneService::class);
        $this->zone = $dnsService->createDefaultZoneForDomain($this->domain, '192.168.10.50');
    }

    public function test_default_zone_creation_provisions_standard_hosting_records(): void
    {
        $this->assertDatabaseHas('dns_zones', ['name' => 'mybrand.org']);
        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'name' => '@',
            'type' => 'A',
            'content' => '192.168.10.50',
        ]);
        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'name' => 'www',
            'type' => 'CNAME',
            'content' => 'mybrand.org.',
        ]);
        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'name' => '@',
            'type' => 'MX',
            'content' => 'mail.mybrand.org.',
            'priority' => 10,
        ]);
        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'name' => '@',
            'type' => 'TXT',
            'content' => 'v=spf1 a mx ~all',
        ]);
    }

    public function test_admin_and_customer_can_view_dns_zone_editor(): void
    {
        $adminRes = $this->actingAs($this->admin)->get(route('admin.dns.index'));
        $adminRes->assertStatus(200);
        $adminRes->assertSeeText('DNS Zone Editor');
        $adminRes->assertSee('mybrand.org');

        $custRes = $this->actingAs($this->customerUser)->get(route('customer.dns.index'));
        $custRes->assertStatus(200);
        $custRes->assertSeeText('DNS Zone Editor');
        $custRes->assertSee('mybrand.org');
    }

    public function test_can_add_custom_record_and_serial_increments(): void
    {
        $initialSerial = $this->zone->serial;

        Livewire::actingAs($this->admin)
            ->test(DnsZoneEditor::class, ['zoneId' => $this->zone->id])
            ->call('openAddModal')
            ->set('recordName', 'api')
            ->set('recordType', DnsRecord::TYPE_A)
            ->set('recordContent', '192.168.10.99')
            ->set('recordTtl', 1800)
            ->call('saveRecord')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'name' => 'api',
            'type' => 'A',
            'content' => '192.168.10.99',
            'ttl' => 1800,
        ]);

        $this->zone->refresh();
        $this->assertGreaterThan($initialSerial, $this->zone->serial);
    }

    public function test_can_delete_dns_record(): void
    {
        $record = $this->zone->records()->where('name', 'ftp')->first();
        $this->assertNotNull($record);

        Livewire::actingAs($this->admin)
            ->test(DnsZoneEditor::class, ['zoneId' => $this->zone->id])
            ->call('deleteRecord', $record->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('dns_records', ['id' => $record->id]);
    }

    public function test_can_view_raw_zone_file_syntax(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DnsZoneEditor::class, ['zoneId' => $this->zone->id])
            ->call('viewRawZone')
            ->assertSet('showRawZoneModal', true)
            ->assertSee('$ORIGIN mybrand.org.')
            ->assertSee('IN SOA ns1.vortan.local.');
    }
}
