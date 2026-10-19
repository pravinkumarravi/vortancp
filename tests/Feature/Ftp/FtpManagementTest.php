<?php

declare(strict_types=1);

namespace Tests\Feature\Ftp;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Ftp\Livewire\FtpIndex;
use App\Modules\Ftp\Models\FtpAccount;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FtpManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'FTP Master 01',
            'hostname' => 'ftp1.vortancp.local',
            'ip_address' => '10.0.0.12',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Standard Cloud',
            'slug' => 'standard-cloud',
            'disk_quota_mb' => 20480,
            'bandwidth_quota_mb' => 204800,
            'max_domains' => 10,
            'max_subdomains' => 20,
            'max_databases' => 10,
            'max_mailboxes' => 20,
            'max_ftp_accounts' => 5,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Nexus Corp',
            'email' => $this->customerUser->email,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $server->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => 'nexus',
            'primary_domain' => 'nexuscorp.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1008,
            'gid' => 1008,
            'homedir' => '/home/nexus',
        ]);
    }

    public function test_admin_and_customer_can_view_ftp_manager(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.ftp.index'))
            ->assertOk()
            ->assertSeeText('FTP & SFTP Accounts');

        $this->actingAs($this->customerUser)
            ->get(route('customer.ftp.index'))
            ->assertOk()
            ->assertSeeText('FTP & SFTP Accounts');
    }

    public function test_can_create_ftp_account_and_manage_via_livewire(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(FtpIndex::class)
            ->set('login_user', 'webmaster')
            ->set('password', 'SuperSecretPass999!')
            ->set('directory', 'public_html')
            ->set('quota_mb', 1000)
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertSee('webmaster@nexuscorp.com');

        $this->assertDatabaseHas('ftp_accounts', [
            'username' => 'webmaster@nexuscorp.com',
            'homedir' => '/home/nexus/public_html',
            'quota_mb' => 1000,
        ]);

        $ftpAccount = FtpAccount::where('username', 'webmaster@nexuscorp.com')->first();

        // Change password
        Livewire::actingAs($this->customerUser)
            ->test(FtpIndex::class)
            ->call('openPasswordModal', $ftpAccount->id)
            ->set('new_password', 'UpdatedPassword2026!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        // Delete account
        Livewire::actingAs($this->customerUser)
            ->test(FtpIndex::class)
            ->call('deleteAccount', $ftpAccount->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('ftp_accounts', [
            'id' => $ftpAccount->id,
        ]);
    }
}
