<?php

declare(strict_types=1);

namespace Tests\Unit\Ftp;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Ftp\Models\FtpAccount;
use App\Modules\Ftp\Services\FtpManagerService;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\HostingPlans\Services\EntitlementService;
use App\Models\User;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class FtpManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FtpManagerService $ftpService;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpService = new FtpManagerService(
            app(EntitlementService::class),
            app(AuditLogger::class)
        );

        $server = Server::create([
            'name' => 'Web Node 01',
            'hostname' => 'node1.vortancp.local',
            'ip_address' => '10.0.0.10',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'disk_quota_mb' => 10240,
            'bandwidth_quota_mb' => 102400,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 10,
            'max_ftp_accounts' => 5,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Apex Agency',
            'email' => $user->email,
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
            'username' => 'apex',
            'primary_domain' => 'apexagency.test',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1005,
            'gid' => 1005,
            'homedir' => '/home/apex',
        ]);
    }

    public function test_can_create_ftp_account(): void
    {
        $ftpAccount = $this->ftpService->createAccount(
            account: $this->account,
            loginPart: 'dev',
            password: 'StrongSecretPass123!',
            relativePath: 'public_html/assets',
            quotaMb: 500,
            uploadKb: 2048,
            downloadKb: 4096
        );

        $this->assertInstanceOf(FtpAccount::class, $ftpAccount);
        $this->assertEquals('dev@apexagency.test', $ftpAccount->username);
        $this->assertEquals('/home/apex/public_html/assets', $ftpAccount->homedir);
        $this->assertTrue(Hash::check('StrongSecretPass123!', $ftpAccount->password_hash));
        $this->assertEquals(500, $ftpAccount->quota_mb);
        $this->assertTrue($ftpAccount->is_active);
    }

    public function test_blocks_path_traversal_on_ftp_homedir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path traversal or null bytes detected');

        $this->ftpService->createAccount(
            account: $this->account,
            loginPart: 'hacker',
            password: 'StrongSecretPass123!',
            relativePath: '../../etc'
        );
    }

    public function test_compiles_pure_ftpd_and_proftpd_user_entries(): void
    {
        $ftpAccount = $this->ftpService->createAccount(
            account: $this->account,
            loginPart: 'media',
            password: 'SecretPass123!',
            relativePath: 'public_html/uploads',
            quotaMb: 250,
            uploadKb: 1024,
            downloadKb: 2048
        );

        $pureFtpdEntry = $this->ftpService->compilePureFtpdPasswdEntry($ftpAccount, 1005, 1005);
        $proftpdEntry = $this->ftpService->compileProftpdUserEntry($ftpAccount, 1005, 1005);

        $this->assertStringContainsString('media@apexagency.test:', $pureFtpdEntry);
        $this->assertStringContainsString('/home/apex/public_html/uploads', $pureFtpdEntry);
        $this->assertStringContainsString(':1024:2048::250:', $pureFtpdEntry);

        $this->assertStringContainsString('media@apexagency.test:', $proftpdEntry);
        $this->assertStringContainsString('/bin/false', $proftpdEntry);
    }

    public function test_can_update_password_and_toggle_status(): void
    {
        $ftpAccount = $this->ftpService->createAccount(
            account: $this->account,
            loginPart: 'backup',
            password: 'OldPassword123!',
            relativePath: 'backups'
        );

        $this->ftpService->updatePassword($ftpAccount, 'BrandNewPassword456!');
        $ftpAccount->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword456!', $ftpAccount->password_hash));

        $this->ftpService->toggleStatus($ftpAccount);
        $ftpAccount->refresh();
        $this->assertFalse($ftpAccount->is_active);

        $this->ftpService->deleteAccount($ftpAccount);
        $this->assertDatabaseMissing('ftp_accounts', ['id' => $ftpAccount->id]);
    }
}
