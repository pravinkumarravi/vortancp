<?php

declare(strict_types=1);

namespace App\Modules\HostingPlans\Database\Seeders;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Resellers\Models\Reseller;
use App\Modules\Servers\Models\Server;
use Illuminate\Database\Seeder;

class HostingPlansDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Standard Hosting Plans
        $starterPlan = HostingPlan::firstOrCreate(
            ['slug' => 'starter-plan'],
            [
                'name' => 'Starter Cloud',
                'description' => 'Entry-level package for personal blogs and portfolios.',
                'max_domains' => 1,
                'max_subdomains' => 2,
                'max_disk_mb' => 2048, // 2 GB
                'max_bandwidth_mb' => 20480, // 20 GB
                'max_databases' => 1,
                'max_mailboxes' => 2,
                'max_ftp_accounts' => 1,
                'max_cron_jobs' => 2,
                'has_ssh_access' => false,
                'has_ssl' => true,
                'has_git' => false,
            ]
        );

        $proPlan = HostingPlan::firstOrCreate(
            ['slug' => 'pro-developer'],
            [
                'name' => 'Pro Developer',
                'description' => 'Feature-rich hosting plan for business sites, web apps, and CMS.',
                'max_domains' => 5,
                'max_subdomains' => 10,
                'max_disk_mb' => 10240, // 10 GB
                'max_bandwidth_mb' => 102400, // 100 GB
                'max_databases' => 10,
                'max_mailboxes' => 25,
                'max_ftp_accounts' => 5,
                'max_cron_jobs' => 10,
                'has_ssh_access' => true,
                'has_ssl' => true,
                'has_git' => true,
            ]
        );

        // 2. Setup Demo Reseller Entity
        $resellerUser = User::where('email', 'reseller@vortan.local')->first();
        if ($resellerUser) {
            $reseller = Reseller::firstOrCreate(
                ['user_id' => $resellerUser->id],
                [
                    'name' => 'Apex Cloud Reseller',
                    'status' => 'active',
                    'max_customers' => 25,
                    'max_disk_mb' => 51200, // 50 GB
                    'max_bandwidth_mb' => 524288, // 500 GB
                    'max_domains' => 50,
                ]
            );
        }

        // 3. Setup Demo Customer Entity
        $customerUser = User::where('email', 'customer@vortan.local')->first();
        $server = Server::first();

        if ($customerUser && $server) {
            $customer = Customer::firstOrCreate(
                ['email' => 'customer@vortan.local'],
                [
                    'user_id' => $customerUser->id,
                    'reseller_id' => $reseller->id ?? null,
                    'name' => 'Acme Web Client',
                    'company_name' => 'Acme Digital Inc',
                    'phone' => '+1-555-0199',
                    'status' => 'active',
                ]
            );

            // 4. Create Customer Subscription
            $subscription = Subscription::firstOrCreate(
                ['customer_id' => $customer->id, 'hosting_plan_id' => $proPlan->id],
                [
                    'server_id' => $server->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                ]
            );

            // 5. Create Isolated Hosting Account (Linux Sandbox)
            $account = HostingAccount::firstOrCreate(
                ['username' => 'acmeclient'],
                [
                    'customer_id' => $customer->id,
                    'subscription_id' => $subscription->id,
                    'server_id' => $server->id,
                    'primary_domain' => 'acmeweb.com',
                    'status' => 'active',
                    'uid' => 1001,
                    'gid' => 1001,
                    'homedir' => '/home/acmeclient',
                    'disk_used_mb' => 1250,
                    'bandwidth_used_mb' => 4500,
                ]
            );

            $subscription->update(['hosting_account_id' => $account->id]);
        }
    }
}
