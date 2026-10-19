<?php

declare(strict_types=1);

namespace Tests\Feature\Email;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Email\Livewire\MailboxManager;
use App\Modules\Email\Models\Mailbox;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private HostingAccount $account;
    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $server = Server::create([
            'name' => 'Mail Server 01',
            'hostname' => 'mail01.vortan.local',
            'ip_address' => '10.0.0.30',
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
            'name' => 'Mail Tier',
            'slug' => 'mail-tier',
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
            'username' => 'acmemail',
            'primary_domain' => 'acme-corp.org',
            'status' => 'active',
            'uid' => 1035,
            'gid' => 1035,
            'homedir' => '/home/acmemail',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'acme-corp.org',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/home/acmemail/public_html',
            'status' => 'active',
        ]);
    }

    public function test_admin_and_customer_can_view_email_manager(): void
    {
        $adminRes = $this->actingAs($this->admin)->get(route('admin.email.index'));
        $adminRes->assertStatus(200);
        $adminRes->assertSeeText('Email Accounts & Mailboxes');

        $custRes = $this->actingAs($this->customerUser)->get(route('customer.email.index'));
        $custRes->assertStatus(200);
        $custRes->assertSeeText('Email Accounts & Mailboxes');
    }

    public function test_can_create_mailbox_and_forwarder_via_livewire(): void
    {
        // 1. Create Mailbox
        Livewire::actingAs($this->admin)
            ->test(MailboxManager::class, ['accountId' => $this->account->id])
            ->call('openCreateMailboxModal')
            ->set('selectedDomainId', $this->domain->id)
            ->set('mailboxUser', 'contact')
            ->set('mailboxPassword', 'SecretEmailPass#2026')
            ->set('mailboxQuotaMb', 2048)
            ->call('createMailbox')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mailboxes', [
            'email' => 'contact@acme-corp.org',
            'quota_mb' => 2048,
        ]);

        $mb = Mailbox::where('email', 'contact@acme-corp.org')->first();

        // 2. Create Forwarder
        Livewire::actingAs($this->admin)
            ->test(MailboxManager::class, ['accountId' => $this->account->id])
            ->call('openCreateForwarderModal')
            ->set('selectedDomainId', $this->domain->id)
            ->set('forwarderSource', 'hello')
            ->set('forwarderDestination', 'hello-external@gmail.com')
            ->call('createForwarder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mail_aliases', [
            'source_email' => 'hello@acme-corp.org',
            'destination_email' => 'hello-external@gmail.com',
        ]);

        // 3. Delete Mailbox
        Livewire::actingAs($this->admin)
            ->test(MailboxManager::class, ['accountId' => $this->account->id])
            ->call('deleteMailbox', $mb->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('mailboxes', ['id' => $mb->id]);
    }
}
