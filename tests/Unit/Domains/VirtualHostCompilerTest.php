<?php

declare(strict_types=1);

namespace Tests\Unit\Domains;

use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use App\Modules\Domains\Services\VirtualHostCompilerService;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Servers\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VirtualHostCompilerTest extends TestCase
{
    use RefreshDatabase;

    private VirtualHostCompilerService $compiler;
    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new VirtualHostCompilerService();

        $server = Server::create([
            'name' => 'Web Node 01',
            'hostname' => 'web01.vortan.local',
            'ip_address' => '10.0.0.1',
            'status' => 'active',
            'is_local' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Acme Corp',
            'email' => 'admin@acme.com',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic',
            'disk_quota_mb' => 5000,
            'bandwidth_quota_mb' => 50000,
            'max_domains' => 5,
            'max_subdomains' => 10,
            'max_databases' => 5,
            'max_mailboxes' => 5,
            'max_ftp_accounts' => 5,
            'max_cron_jobs' => 5,
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

        $account = HostingAccount::create([
            'customer_id' => $customer->id,
            'subscription_id' => $sub->id,
            'server_id' => $server->id,
            'username' => 'acmeweb',
            'primary_domain' => 'acmeweb.com',
            'status' => 'active',
            'uid' => 1002,
            'gid' => 1002,
            'homedir' => '/home/acmeweb',
        ]);

        $this->domain = Domain::create([
            'hosting_account_id' => $account->id,
            'domain' => 'acmeweb.com',
            'type' => Domain::TYPE_PRIMARY,
            'document_root' => '/home/acmeweb/public_html',
            'php_version' => '8.4',
            'status' => Domain::STATUS_ACTIVE,
            'force_https' => true,
            'www_redirect' => 'none',
        ]);
    }

    public function test_compiles_clean_nginx_vhost_with_fpm_and_security_headers(): void
    {
        $config = $this->compiler->compileNginx($this->domain);

        $this->assertStringContainsString('server_name acmeweb.com www.acmeweb.com;', $config);
        $this->assertStringContainsString('root /home/acmeweb/public_html;', $config);
        $this->assertStringContainsString('fastcgi_pass unix:/run/php/php8.4-fpm.sock;', $config);
        $this->assertStringContainsString('X-Frame-Options "SAMEORIGIN"', $config);
        $this->assertStringContainsString('deny all;', $config); // Hidden files protection
        $this->assertStringContainsString('access_log /var/log/vortan/acmeweb/acmeweb.com.access.log', $config);
    }

    public function test_compiles_https_server_block_with_port_80_redirect(): void
    {
        $config = $this->compiler->compileNginx(
            domain: $this->domain,
            hasSsl: true,
            certPath: '/etc/ssl/vortan/acmeweb.com.crt',
            keyPath: '/etc/ssl/vortan/acmeweb.com.key'
        );

        $this->assertStringContainsString('listen 80;', $config);
        $this->assertStringContainsString('return 301 https://$host$request_uri;', $config);
        $this->assertStringContainsString('listen 443 ssl http2;', $config);
        $this->assertStringContainsString('ssl_certificate /etc/ssl/vortan/acmeweb.com.crt;', $config);
        $this->assertStringContainsString('ssl_certificate_key /etc/ssl/vortan/acmeweb.com.key;', $config);
    }
}
