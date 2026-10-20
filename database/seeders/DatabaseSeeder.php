<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Billing\Models\ApiKey;
use App\Modules\Databases\Models\Database;
use App\Modules\Databases\Models\DatabaseUser;
use App\Modules\Databases\Models\DatabaseUserAssignment;
use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Domains\Models\Domain;
use App\Modules\Domains\Models\VirtualHost;
use App\Modules\Email\Models\Mailbox;
use App\Modules\Email\Models\MailDomain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use App\Modules\Metrics\Models\AccountMetric;
use App\Modules\Metrics\Models\ServerMetric;
use App\Modules\Resellers\Models\Reseller;
use App\Modules\Servers\Models\Server;
use App\Modules\WordPressToolkit\Models\WordPressInstance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Core Identity & RBAC
        $this->call(IdentityDatabaseSeeder::class);

        $adminUser = User::where('email', 'admin@vortan.local')->first();
        $resellerUser = User::where('email', 'reseller@vortan.local')->first();
        $customerUser = User::where('email', 'customer@vortan.local')->first();

        // 2. Cluster Server Nodes
        $primaryServer = Server::firstOrCreate(
            ['hostname' => 'node01.vortancp.local'],
            [
                'name' => 'US-East Production Node 01',
                'ip_address' => '198.51.100.10',
                'status' => Server::STATUS_ACTIVE,
                'connection_type' => Server::CONNECTION_LOCAL,
                'auth_token' => 'cluster_agent_secret_node01',
            ]
        );

        // 3. Hosting Plans
        $starterPlan = HostingPlan::firstOrCreate(
            ['slug' => 'starter-cloud'],
            [
                'name' => 'Starter Cloud',
                'description' => 'Ideal for personal sites, small blogs, and starter portfolios.',
                'max_disk_mb' => 10240,
                'max_bandwidth_mb' => 51200,
                'max_domains' => 1,
                'max_subdomains' => 3,
                'max_databases' => 2,
                'max_mailboxes' => 5,
                'max_ftp_accounts' => 2,
                'max_cron_jobs' => 2,
                'has_ssl' => true,
                'has_dns_management' => true,
                'has_ssh_access' => false,
                'has_git' => false,
                'has_wordpress_toolkit' => true,
                'is_active' => true,
            ]
        );

        $proPlan = HostingPlan::firstOrCreate(
            ['slug' => 'developer-pro'],
            [
                'name' => 'Developer Pro',
                'description' => 'Optimized for dynamic web applications, staging pipelines, and e-commerce.',
                'max_disk_mb' => 51200,
                'max_bandwidth_mb' => 256000,
                'max_domains' => 5,
                'max_subdomains' => 15,
                'max_databases' => 10,
                'max_mailboxes' => 20,
                'max_ftp_accounts' => 10,
                'max_cron_jobs' => 10,
                'has_ssl' => true,
                'has_dns_management' => true,
                'has_ssh_access' => true,
                'has_git' => true,
                'has_wordpress_toolkit' => true,
                'is_active' => true,
            ]
        );

        // 4. Reseller Profile
        $reseller = Reseller::firstOrCreate(
            ['user_id' => $resellerUser->id],
            [
                'company_name' => 'Apex Cloud Services',
                'brand_name' => 'ApexHost',
                'status' => 'active',
            ]
        );

        // 5. Customer Profile
        $customer = Customer::firstOrCreate(
            ['user_id' => $customerUser->id],
            [
                'name' => 'Acme Web Technologies',
                'email' => $customerUser->email,
                'status' => Customer::STATUS_ACTIVE,
            ]
        );

        // 6. Subscription
        $subscription = Subscription::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'hosting_plan_id' => $proPlan->id,
                'server_id' => $primaryServer->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
            ]
        );

        // 7. Hosting Account
        $account = HostingAccount::firstOrCreate(
            ['username' => 'acmecorp'],
            [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'server_id' => $primaryServer->id,
                'primary_domain' => 'acmecorp.com',
                'status' => HostingAccount::STATUS_ACTIVE,
                'uid' => 1050,
                'gid' => 1050,
                'homedir' => '/var/vortan/users/acmecorp',
            ]
        );

        // 8. Domain & Virtual Host
        $domain = Domain::firstOrCreate(
            ['domain' => 'acmecorp.com'],
            [
                'hosting_account_id' => $account->id,
                'type' => Domain::TYPE_PRIMARY,
                'document_root' => '/var/vortan/users/acmecorp/public_html',
                'php_version' => '8.3',
                'force_https' => true,
                'status' => Domain::STATUS_ACTIVE,
            ]
        );

        VirtualHost::firstOrCreate(
            ['domain_id' => $domain->id],
            [
                'server_id' => $primaryServer->id,
                'webserver_type' => 'nginx',
                'config_path' => "/etc/nginx/sites-available/{$domain->domain}.conf",
                'is_ssl_enabled' => false,
                'last_compiled_at' => now(),
            ]
        );

        // 9. DNS Zone & Records
        $zone = DnsZone::firstOrCreate(
            ['name' => 'acmecorp.com'],
            [
                'domain_id' => $domain->id,
                'hosting_account_id' => $account->id,
                'primary_ns' => 'ns1.vortancp.local',
                'admin_email' => 'hostmaster.acmecorp.com',
                'serial' => (int) date('Ymd01'),
                'status' => 'active',
            ]
        );

        DnsRecord::firstOrCreate(
            ['dns_zone_id' => $zone->id, 'type' => 'A', 'name' => '@'],
            ['content' => $primaryServer->ip_address, 'ttl' => 3600]
        );
        DnsRecord::firstOrCreate(
            ['dns_zone_id' => $zone->id, 'type' => 'CNAME', 'name' => 'www'],
            ['content' => 'acmecorp.com.', 'ttl' => 3600]
        );
        DnsRecord::firstOrCreate(
            ['dns_zone_id' => $zone->id, 'type' => 'MX', 'name' => '@'],
            ['content' => 'mail.acmecorp.com.', 'priority' => 10, 'ttl' => 3600]
        );

        // 10. Database & User
        $database = Database::firstOrCreate(
            ['name' => 'acmecorp_portal'],
            [
                'hosting_account_id' => $account->id,
                'server_id' => $primaryServer->id,
                'db_engine' => 'mysql',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'size_bytes' => 1048576 * 14, // 14 MB
                'status' => Database::STATUS_ACTIVE,
            ]
        );

        $dbUser = DatabaseUser::firstOrCreate(
            ['username' => 'acmecorp_app'],
            [
                'hosting_account_id' => $account->id,
                'server_id' => $primaryServer->id,
                'host' => 'localhost',
                'password_hash' => Hash::make('AcmeSecretPass#2026!'),
                'status' => DatabaseUser::STATUS_ACTIVE,
            ]
        );

        DatabaseUserAssignment::firstOrCreate(
            ['database_id' => $database->id, 'database_user_id' => $dbUser->id],
            ['privileges' => ['ALL']]
        );

        // 11. Mail Domain & Mailbox
        $mailDomain = MailDomain::firstOrCreate(
            ['name' => 'acmecorp.com'],
            [
                'domain_id' => $domain->id,
                'hosting_account_id' => $account->id,
                'dkim_selector' => 'default',
                'dkim_public_key' => 'v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...',
                'status' => MailDomain::STATUS_ACTIVE,
            ]
        );

        Mailbox::firstOrCreate(
            ['email' => 'contact@acmecorp.com'],
            [
                'mail_domain_id' => $mailDomain->id,
                'hosting_account_id' => $account->id,
                'local_part' => 'contact',
                'password_hash' => Hash::make('MailboxSecret#2026!'),
                'quota_mb' => 5120, // 5 GB
                'used_mb' => 128,   // 128 MB
                'status' => Mailbox::STATUS_ACTIVE,
            ]
        );

        // 12. WordPress Instance
        WordPressInstance::firstOrCreate(
            ['site_url' => 'https://acmecorp.com'],
            [
                'hosting_account_id' => $account->id,
                'domain_id' => $domain->id,
                'site_title' => 'Acme Corporate Portal',
                'path' => '/var/vortan/users/acmecorp/public_html',
                'db_name' => $database->name,
                'db_user' => $dbUser->username,
                'wp_version' => '6.7.1',
                'admin_email' => 'admin@acmecorp.com',
                'status' => WordPressInstance::STATUS_ACTIVE,
                'auto_update_core' => true,
                'xmlrpc_blocked' => true,
                'file_editing_disabled' => true,
                'table_prefix' => 'wp_acme_',
                'last_scanned_at' => now(),
            ]
        );

        // 13. Telemetry & Metrics Snapshots
        ServerMetric::create([
            'server_id' => $primaryServer->id,
            'cpu_pct' => 14.50,
            'memory_pct' => 38.20,
            'memory_used_mb' => 6250,
            'memory_total_mb' => 16384,
            'disk_pct' => 24.80,
            'disk_used_mb' => 24800,
            'disk_total_mb' => 100000,
            'load_1min' => 0.42,
            'recorded_at' => now(),
        ]);

        AccountMetric::create([
            'hosting_account_id' => $account->id,
            'disk_used_mb' => 1420,
            'bandwidth_used_mb' => 12450,
            'inodes_used' => 8420,
            'recorded_at' => now(),
        ]);

        // 14. Default WHMCS Billing API Key
        ApiKey::firstOrCreate(
            ['name' => 'Default WHMCS Integration Key'],
            [
                'key_prefix' => 'vortan_demo1234',
                'key_hash' => hash('sha256', 'vortan_demo1234567890abcdef1234567890abcdef'),
                'abilities' => ['*'],
                'is_active' => true,
            ]
        );

        // 15. Monitored System Daemons
        \App\Modules\ServiceManager\Models\MonitoredService::seedDefaults();
    }
}
