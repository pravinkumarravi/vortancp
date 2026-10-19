<?php

declare(strict_types=1);

namespace Tests\Feature\Git;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Git\Livewire\GitIndex;
use App\Modules\Git\Models\GitRepository;
use App\Modules\Git\Services\GitRepositoryService;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class GitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected HostingAccount $account;
    protected Domain $domain;
    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'Git Test Server',
            'hostname' => 'gittest.vortancp.local',
            'ip_address' => '10.0.0.51',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $plan = HostingPlan::create([
            'name' => 'Git Enabled Plan',
            'slug' => 'git-plan',
            'max_disk_mb' => 10000,
            'max_bandwidth_mb' => 50000,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
            'has_git' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Git Corp',
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

        $this->testDir = sys_get_temp_dir() . '/vortan_git_test_' . uniqid();
        File::makeDirectory($this->testDir, 0755, true);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'server_id' => $server->id,
            'username' => 'gituser',
            'primary_domain' => 'mygitsite.com',
            'status' => HostingAccount::STATUS_ACTIVE,
            'uid' => 1026,
            'gid' => 1026,
            'homedir' => $this->testDir,
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'mygitsite.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => "{$this->testDir}/public_html",
            'php_version' => '8.3',
            'status' => Domain::STATUS_ACTIVE,
        ]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }
        parent::tearDown();
    }

    public function test_can_render_git_index_as_admin_and_customer(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.git.index'))
            ->assertOk();

        $this->actingAs($this->customerUser)
            ->get(route('customer.git.index'))
            ->assertOk();
    }

    public function test_creates_git_repository_and_generates_deploy_key(): void
    {
        $gitService = app(GitRepositoryService::class);

        $repo = $gitService->createRepository(
            account: $this->account,
            name: 'frontend-app',
            domain: $this->domain,
            remoteUrl: 'git@github.com:example/frontend.git',
            branch: 'main'
        );

        $this->assertInstanceOf(GitRepository::class, $repo);
        $this->assertEquals('frontend-app', $repo->name);
        $this->assertNotEmpty($repo->deploy_key_public);
        $this->assertNotEmpty($repo->webhook_secret);
        $this->assertTrue(File::isDirectory($repo->repo_path));
    }

    public function test_prevents_git_creation_when_plan_denies_feature(): void
    {
        $planNoGit = HostingPlan::create([
            'name' => 'No Git Plan',
            'slug' => 'no-git-plan',
            'has_git' => false,
            'is_active' => true,
        ]);

        $this->account->subscription->update(['hosting_plan_id' => $planNoGit->id]);

        $this->expectException(QuotaExceededException::class);

        $gitService = app(GitRepositoryService::class);
        $gitService->createRepository(
            account: $this->account,
            name: 'forbidden-repo'
        );
    }

    public function test_deploys_repository_and_updates_release_marker(): void
    {
        $gitService = app(GitRepositoryService::class);

        $repo = $gitService->createRepository(
            account: $this->account,
            name: 'api-service',
            domain: $this->domain
        );

        $result = $gitService->deploy($repo, commitHash: 'abc12345def', commitMessage: 'Initial release', author: 'DevOps');

        $this->assertTrue($result['success']);
        $this->assertEquals('abc12345def', $result['commit']);

        $repo->refresh();
        $this->assertEquals('abc12345def', $repo->last_commit_hash);
        $this->assertEquals('Initial release', $repo->last_commit_message);
        $this->assertNotNull($repo->last_deployed_at);

        $releaseFile = "{$repo->deploy_path}/.vortan_release";
        $this->assertTrue(File::exists($releaseFile));
    }

    public function test_webhook_triggers_deployment(): void
    {
        $gitService = app(GitRepositoryService::class);

        $repo = $gitService->createRepository(
            account: $this->account,
            name: 'webhook-repo',
            domain: $this->domain,
            branch: 'main'
        );

        $response = $this->postJson(route('git.webhook', ['repo' => $repo->id]), [
            'ref' => 'refs/heads/main',
            'head_commit' => [
                'id' => '112233445566',
                'message' => 'Fix security vulnerability',
                'author' => ['name' => 'Alice'],
            ],
        ], [
            'X-Webhook-Token' => $repo->webhook_secret,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $repo->refresh();
        $this->assertEquals('112233445566', $repo->last_commit_hash);
        $this->assertEquals('Fix security vulnerability', $repo->last_commit_message);
    }

    public function test_deletes_repository_and_cleans_directory(): void
    {
        $gitService = app(GitRepositoryService::class);

        $repo = $gitService->createRepository(
            account: $this->account,
            name: 'to-delete'
        );

        $id = $repo->id;
        $path = $repo->repo_path;

        $gitService->deleteRepository($repo);

        $this->assertNull(GitRepository::find($id));
        $this->assertFalse(File::isDirectory($path));
    }

    public function test_livewire_component_interactivity(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(GitIndex::class, ['accountId' => $this->account->id])
            ->assertSet('selectedAccountId', $this->account->id)
            ->set('name', 'livewire-repo')
            ->set('branch', 'master')
            ->call('createRepository')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('git_repositories', [
            'hosting_account_id' => $this->account->id,
            'name' => 'livewire-repo',
            'branch' => 'master',
        ]);
    }
}
