<?php

declare(strict_types=1);

namespace Tests\Feature\EmailSecurity;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\EmailSecurity\Livewire\EmailSecurityManager;
use App\Modules\EmailSecurity\Models\EmailSecurityPolicy;
use App\Modules\EmailSecurity\Services\EmailSecurityService;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailSecurityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Server $server;
    protected HostingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IdentityDatabaseSeeder::class);

        $this->adminUser = User::where('email', 'admin@vortan.local')->first();
        $this->customerUser = User::where('email', 'customer@vortan.local')->first();

        $this->server = Server::create([
            'name' => 'Mail Gateway Node',
            'hostname' => 'mx1.vortancp.local',
            'ip_address' => '10.0.0.15',
            'status' => Server::STATUS_ACTIVE,
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Email Security Client',
            'email' => $this->customerUser->email,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->account = HostingAccount::create([
            'customer_id' => $customer->id,
            'server_id' => $this->server->id,
            'username' => 'secmailclient',
            'primary_domain' => 'securemail.example.org',
            'homedir' => '/var/vortan/vhosts/secmailclient',
            'ip_address' => '10.0.0.15',
            'status' => HostingAccount::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_access_email_security_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/email-security');

        $response->assertStatus(200);
        $response->assertSee('Email Security');
        $response->assertSee('Anti-Spam Protection');
    }

    public function test_customer_can_access_email_security_page(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/app/email-security');

        $response->assertStatus(200);
        $response->assertSee('Email Security');
        $response->assertSee('Anti-Spam Protection');
    }

    public function test_customer_can_update_security_policy_thresholds(): void
    {
        Livewire::actingAs($this->customerUser)
            ->test(EmailSecurityManager::class)
            ->set('required_score', 4.5)
            ->set('reject_score', 12.0)
            ->set('spam_subject_tag', '[POSSIBLE SPAM]')
            ->set('auto_delete_spam', true)
            ->call('savePolicy')
            ->assertHasNoErrors()
            ->assertSee('Email anti-spam and security thresholds updated successfully');

        $policy = EmailSecurityPolicy::where('hosting_account_id', $this->account->id)->first();
        $this->assertNotNull($policy);
        $this->assertEquals(4.5, (float) $policy->required_score);
        $this->assertEquals(12.0, (float) $policy->reject_score);
        $this->assertEquals('[POSSIBLE SPAM]', $policy->spam_subject_tag);
        $this->assertTrue($policy->auto_delete_spam);
    }

    public function test_can_manage_allowlist_and_blocklist_entries(): void
    {
        $component = Livewire::actingAs($this->customerUser)
            ->test(EmailSecurityManager::class)
            ->set('newAllowlistEntry', 'trusted-partner@corp.com')
            ->call('addAllowlist')
            ->assertSee('trusted-partner@corp.com')
            ->set('newBlocklistEntry', '*@phishing-attack.net')
            ->call('addBlocklist')
            ->assertSee('*@phishing-attack.net');

        $policy = EmailSecurityPolicy::where('hosting_account_id', $this->account->id)->first();
        $this->assertContains('trusted-partner@corp.com', $policy->allowlist);
        $this->assertContains('*@phishing-attack.net', $policy->blocklist);

        // Remove entries
        $component->call('removeAllowlist', 'trusted-partner@corp.com')
            ->call('removeBlocklist', '*@phishing-attack.net');

        $policy->refresh();
        $this->assertNotContains('trusted-partner@corp.com', $policy->allowlist);
        $this->assertNotContains('*@phishing-attack.net', $policy->blocklist);
    }

    public function test_email_security_heuristics_and_message_evaluation(): void
    {
        $service = app(EmailSecurityService::class);
        $policy = $service->getOrCreatePolicy($this->account);
        $service->addBlocklistEntry($policy, 'banned-sender@baddomain.org');

        // Test blocked sender
        $blockedEvaluation = $service->evaluateMessage(
            'banned-sender@baddomain.org',
            'Hello friend',
            'Just saying hi',
            $policy
        );
        $this->assertEquals('reject', $blockedEvaluation['action']);
        $this->assertTrue($blockedEvaluation['is_spam']);

        // Test clean message
        $cleanEvaluation = $service->evaluateMessage(
            'colleague@trustedwork.com',
            'Quarterly Roadmap Review',
            'Please find the attached roadmap document for our upcoming sprint review.',
            $policy
        );
        $this->assertEquals('accept', $cleanEvaluation['action']);
        $this->assertFalse($cleanEvaluation['is_spam']);

        // Test Livewire evaluation simulator
        Livewire::actingAs($this->customerUser)
            ->test(EmailSecurityManager::class)
            ->set('testSender', 'suspicious@wildspammer.com')
            ->set('testSubject', 'URGENT: WINNER OF CASH PRIZE CLAIM NOW!!!')
            ->set('testBody', 'Congratulations! WIRE TRANSFER needed immediately. CONFIRM YOUR PASSWORD.')
            ->call('evaluateTestMessage')
            ->assertSee('Verdict: TAG')
            ->assertSee('SUBJ_ALL_CAPS')
            ->assertSee('SPAM_KEYWORD_WIRE TRANSFER');
    }
}
