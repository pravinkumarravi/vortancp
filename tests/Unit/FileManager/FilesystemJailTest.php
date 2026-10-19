<?php

declare(strict_types=1);

namespace Tests\Unit\FileManager;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\FileManager\Services\FilesystemJailService;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Tests\TestCase;

class FilesystemJailTest extends TestCase
{
    use RefreshDatabase;

    private FilesystemJailService $jail;
    private HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jail = new FilesystemJailService(app(AuditLogger::class));

        $server = Server::create([
            'name' => 'Storage Node 01',
            'hostname' => 'storage01.vortan.local',
            'ip_address' => '10.0.0.10',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Acme User',
            'email' => 'user@acme.com',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Jail Plan',
            'slug' => 'jail-plan',
            'disk_quota_mb' => 2000,
            'bandwidth_quota_mb' => 20000,
            'max_domains' => 2,
            'max_subdomains' => 2,
            'max_databases' => 2,
            'max_mailboxes' => 2,
            'max_ftp_accounts' => 2,
            'max_cron_jobs' => 2,
            'has_ssh_access' => false,
            'has_ssl' => true,
            'has_git' => false,
            'has_dns_management' => true,
            'has_wordpress_toolkit' => false,
            'is_active' => true,
        ]);

        $sub = Subscription::create([
            'customer_id' => $customer->id,
            'hosting_plan_id' => $plan->id,
            'server_id' => $server->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $sub->id,
            'server_id' => $server->id,
            'username' => 'testuser',
            'primary_domain' => 'testuser.org',
            'status' => 'active',
            'uid' => 1010,
            'gid' => 1010,
            'homedir' => '/home/testuser',
        ]);
    }

    protected function tearDown(): void
    {
        $dir = storage_path('sandboxes/testuser');
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    public function test_blocks_path_traversal_attempts(): void
    {
        // Try accessing /etc/passwd via ../ traversal
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Path traversal escape attempt detected/');

        $this->jail->resolvePath($this->account, '../../etc/passwd');
    }

    public function test_can_create_write_read_and_delete_file_in_jail(): void
    {
        // Write file
        $this->jail->writeFile($this->account, 'public_html/index.php', '<?php echo "Hello Vortan";');

        // Read file
        $content = $this->jail->readFile($this->account, 'public_html/index.php');
        $this->assertSame('<?php echo "Hello Vortan";', $content);

        // List directory
        $items = $this->jail->listDirectory($this->account, 'public_html');
        $this->assertNotEmpty($items);
        $this->assertSame('index.php', $items[0]['name']);

        // Delete file
        $this->jail->deleteItem($this->account, 'public_html/index.php');
        $this->assertEmpty($this->jail->listDirectory($this->account, 'public_html'));
    }

    public function test_can_create_nested_directory(): void
    {
        $this->jail->createDirectory($this->account, 'public_html/app/config');
        $items = $this->jail->listDirectory($this->account, 'public_html/app');

        $this->assertCount(1, $items);
        $this->assertSame('config', $items[0]['name']);
        $this->assertTrue($items[0]['is_dir']);
    }
}
