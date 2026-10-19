<?php

declare(strict_types=1);

namespace Tests\Unit\Email;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\Email\Services\MailboxManagerService;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\HostingPlans\Services\EntitlementService;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxManagerTest extends TestCase
{
    use RefreshDatabase;

    private MailboxManagerService $mailService;
    private HostingAccount $account;
    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailService = new MailboxManagerService(
            app(EntitlementService::class),
            app(AuditLogger::class)
        );

        $server = Server::create([
            'name' => 'Mail Node 01',
            'hostname' => 'mail01.vortan.local',
            'ip_address' => '10.0.0.25',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Email User',
            'email' => 'postmaster@acme.com',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Mailbox Plan',
            'slug' => 'mailbox-plan',
            'disk_quota_mb' => 2000,
            'bandwidth_quota_mb' => 20000,
            'max_domains' => 2,
            'max_subdomains' => 2,
            'max_databases' => 2,
            'max_mailboxes' => 2, // limit is 2
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
            'username' => 'mailuser',
            'primary_domain' => 'vortanmail.org',
            'status' => 'active',
            'uid' => 1030,
            'gid' => 1030,
            'homedir' => '/home/mailuser',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $this->account->id,
            'domain' => 'vortanmail.org',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/home/mailuser/public_html',
            'status' => 'active',
        ]);
    }

    public function test_ensure_mail_domain_generates_dkim_keypair(): void
    {
        $mailDomain = $this->mailService->ensureMailDomain($this->domain);

        $this->assertSame('vortanmail.org', $mailDomain->name);
        $this->assertNotEmpty($mailDomain->dkim_selector);
        $this->assertNotEmpty($mailDomain->dkim_public_key);
    }

    public function test_creates_mailbox_and_enforces_quota(): void
    {
        $mailDomain = $this->mailService->ensureMailDomain($this->domain);

        // 1st mailbox
        $mb1 = $this->mailService->createMailbox($mailDomain, 'info', 'Password123#', 1024);
        $this->assertSame('info@vortanmail.org', $mb1->email);

        // 2nd mailbox
        $mb2 = $this->mailService->createMailbox($mailDomain, 'support', 'Password123#', 1024);
        $this->assertSame('support@vortanmail.org', $mb2->email);

        // 3rd mailbox should throw QuotaExceededException (max is 2)
        $this->expectException(QuotaExceededException::class);
        $this->expectExceptionMessageMatches('/Resource quota exceeded for mailbox/');

        $this->mailService->createMailbox($mailDomain, 'billing', 'Password123#', 1024);
    }

    public function test_can_create_email_forwarder(): void
    {
        $mailDomain = $this->mailService->ensureMailDomain($this->domain);
        $alias = $this->mailService->createAlias($mailDomain, 'sales@vortanmail.org', 'external-sales@gmail.com');

        $this->assertSame('sales@vortanmail.org', $alias->source_email);
        $this->assertSame('external-sales@gmail.com', $alias->destination_email);
    }
}
