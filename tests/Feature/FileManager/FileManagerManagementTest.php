<?php

declare(strict_types=1);

namespace Tests\Feature\FileManager;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\FileManager\Livewire\FileManagerIndex;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class FileManagerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'Storage Node 01',
            'hostname' => 'storage01.vortan.local',
            'ip_address' => '10.0.0.10',
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
            'name' => 'File Plan',
            'slug' => 'file-plan',
            'disk_quota_mb' => 10240,
            'bandwidth_quota_mb' => 102400,
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
            'username' => 'fileclient',
            'primary_domain' => 'file-client.com',
            'status' => 'active',
            'uid' => 1006,
            'gid' => 1006,
            'homedir' => '/home/fileclient',
        ]);
    }

    protected function tearDown(): void
    {
        $dir = storage_path('sandboxes/fileclient');
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    public function test_admin_and_customer_can_view_file_manager(): void
    {
        $adminRes = $this->actingAs($this->admin)->get(route('admin.files.index'));
        $adminRes->assertStatus(200);
        $adminRes->assertSeeText('File Manager');

        $custRes = $this->actingAs($this->customerUser)->get(route('customer.files.index'));
        $custRes->assertStatus(200);
        $custRes->assertSeeText('File Manager');
    }

    public function test_can_create_file_edit_and_delete_via_livewire(): void
    {
        // 1. Create file
        Livewire::actingAs($this->admin)
            ->test(FileManagerIndex::class, ['accountId' => $this->account->id])
            ->set('currentPath', 'public_html')
            ->call('openCreateModal', 'file')
            ->set('createName', 'test.txt')
            ->call('createItem')
            ->assertHasNoErrors();

        // 2. Open editor & save content
        Livewire::actingAs($this->admin)
            ->test(FileManagerIndex::class, ['accountId' => $this->account->id])
            ->call('openEditor', 'public_html/test.txt')
            ->assertSet('editingFilePath', 'public_html/test.txt')
            ->set('editingFileContent', 'Testing livewire file editor')
            ->call('saveFile')
            ->assertHasNoErrors();

        // 3. Delete item
        Livewire::actingAs($this->admin)
            ->test(FileManagerIndex::class, ['accountId' => $this->account->id])
            ->call('deleteItem', 'public_html/test.txt')
            ->assertHasNoErrors();
    }
}
