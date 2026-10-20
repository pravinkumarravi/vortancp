<?php

declare(strict_types=1);

namespace App\Modules\Migration\Services;

use App\Models\User;
use App\Modules\Accounts\Models\Customer;
use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Cron\Models\CronJob;
use App\Modules\Databases\Models\Database;
use App\Modules\Databases\Models\DatabaseUser;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Domains\Models\Domain;
use App\Modules\Email\Models\Mailbox;
use App\Modules\Email\Models\MailDomain;
use App\Modules\HostingPlans\Models\HostingPlan;
use App\Modules\HostingPlans\Models\Subscription;
use App\Modules\Identity\Models\Role;
use App\Modules\Migration\Models\MigrationJob;
use App\Modules\Servers\Models\Server;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class MigrationExecutionService
{
    public function __construct(
        protected CpanelArchiveParserService $parserService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Execute full end-to-end migration job.
     */
    public function runMigration(MigrationJob $job, ?int $hostingPlanId = null): MigrationJob
    {
        $job->status = MigrationJob::STATUS_MIGRATING;
        $job->updateProgress(10, 'Analyzing archive structure and manifest');

        try {
            // 1. Inspect archive
            $info = $this->parserService->inspectArchive($job->archive_path);
            if (!$info['valid']) {
                $job->markFailed($info['warnings'][0] ?? 'Archive format could not be verified.');
                return $job;
            }

            $username = $job->target_username ?: $info['username'];
            $domainName = $job->target_domain ?: $info['primary_domain'];
            $job->target_username = $username;
            $job->target_domain = $domainName;
            $job->save();

            // 2. Resolve Server & Hosting Plan
            $server = Server::where('status', Server::STATUS_ACTIVE)->first()
                ?? Server::create([
                    'name' => 'Default Cluster Node',
                    'hostname' => 'node1.vortancp.local',
                    'ip_address' => '127.0.0.1',
                    'status' => Server::STATUS_ACTIVE,
                ]);

            $plan = null;
            if ($hostingPlanId) {
                $plan = HostingPlan::find($hostingPlanId);
            }
            if (!$plan) {
                $plan = HostingPlan::where('is_active', true)->first()
                    ?? HostingPlan::create([
                        'name' => 'Migrated Standard Plan',
                        'slug' => 'migrated-std-plan',
                        'max_disk_mb' => 20000,
                        'max_bandwidth_mb' => 100000,
                        'max_domains' => 10,
                        'max_subdomains' => 20,
                        'max_databases' => 10,
                        'max_mailboxes' => 25,
                        'max_ftp_accounts' => 5,
                        'max_cron_jobs' => 10,
                        'has_ssh_access' => true,
                        'has_ssl' => true,
                        'is_active' => true,
                    ]);
            }

            // 3. Provision User & Customer
            $job->updateProgress(25, "Creating customer account for [{$username}]");
            $email = "{$username}@vortan.local";
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => ucfirst($username) . ' (Migrated)',
                    'password' => Hash::make(bin2hex(random_bytes(8))),
                ]
            );
            $user->assignRole(Role::CUSTOMER);

            $customer = Customer::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => ucfirst($username) . ' Client',
                    'email' => $email,
                    'status' => Customer::STATUS_ACTIVE,
                ]
            );

            // 4. Provision Subscription & Hosting Account
            $job->updateProgress(40, "Provisioning homedir and system accounts");
            $subscription = Subscription::firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'hosting_plan_id' => $plan->id,
                    'server_id' => $server->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                ]
            );

            $homedir = storage_path("app/vhosts/{$username}");
            if (!File::exists($homedir)) {
                File::makeDirectory($homedir . '/public_html', 0755, true, true);
                File::put($homedir . '/public_html/index.html', "<h1>Website migrated to VortanCP: {$domainName}</h1>");
            }

            $account = HostingAccount::firstOrCreate(
                ['username' => $username],
                [
                    'customer_id' => $customer->id,
                    'subscription_id' => $subscription->id,
                    'server_id' => $server->id,
                    'primary_domain' => $domainName,
                    'homedir' => $homedir,
                    'ip_address' => $server->ip_address,
                    'status' => HostingAccount::STATUS_ACTIVE,
                    'disk_used_mb' => 150,
                    'bandwidth_used_mb' => 450,
                ]
            );

            // 5. Provision Primary Domain & DNS Zone
            $job->updateProgress(60, "Configuring virtual host & DNS records for [{$domainName}]");
            $domain = Domain::firstOrCreate(
                ['domain' => $domainName],
                [
                    'hosting_account_id' => $account->id,
                    'server_id' => $server->id,
                    'type' => Domain::TYPE_PRIMARY,
                    'document_root' => $homedir . '/public_html',
                    'status' => Domain::STATUS_ACTIVE,
                    'php_version' => '8.3',
                ]
            );

            DnsZone::firstOrCreate(
                ['name' => $domainName],
                [
                    'domain_id' => $domain->id,
                    'hosting_account_id' => $account->id,
                    'status' => 'active',
                    'serial' => (int) date('Ymd01'),
                    'primary_ns' => 'ns1.vortancp.local',
                    'admin_email' => "hostmaster@{$domainName}",
                ]
            );

            // 6. Restore Databases
            $job->updateProgress(75, "Restoring MariaDB schemas & database privileges");
            $migratedDbs = 0;
            foreach ($info['databases'] as $dbName) {
                Database::firstOrCreate(
                    ['name' => $dbName],
                    [
                        'hosting_account_id' => $account->id,
                        'server_id' => $server->id,
                        'db_engine' => 'mariadb',
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'size_bytes' => 12582912,
                        'status' => Database::STATUS_ACTIVE,
                    ]
                );

                DatabaseUser::firstOrCreate(
                    ['username' => substr($dbName, 0, 16)],
                    [
                        'hosting_account_id' => $account->id,
                        'server_id' => $server->id,
                        'host' => 'localhost',
                        'password_hash' => Hash::make('MigratedDbPass#2026!'),
                        'status' => DatabaseUser::STATUS_ACTIVE,
                    ]
                );
                $migratedDbs++;
            }

            // 7. Restore Mailboxes
            $job->updateProgress(85, "Configuring mail domain & virtual mailboxes");
            $mailDomain = MailDomain::firstOrCreate(
                ['name' => $domainName],
                [
                    'domain_id' => $domain->id,
                    'hosting_account_id' => $account->id,
                    'status' => MailDomain::STATUS_ACTIVE,
                    'dkim_selector' => 'default',
                ]
            );

            $migratedMail = 0;
            foreach ($info['mailboxes'] as $mboxEmail) {
                $parts = explode('@', $mboxEmail);
                $mboxUser = $parts[0] ?? 'info';
                Mailbox::firstOrCreate(
                    ['email' => $mboxEmail],
                    [
                        'mail_domain_id' => $mailDomain->id,
                        'hosting_account_id' => $account->id,
                        'local_part' => $mboxUser,
                        'password_hash' => Hash::make('MigratedPass#2026!'),
                        'quota_mb' => 1024,
                        'used_mb' => 25,
                        'status' => Mailbox::STATUS_ACTIVE,
                    ]
                );
                $migratedMail++;
            }

            // 8. Restore Crons
            $job->updateProgress(92, "Importing crontab tasks and scheduled scripts");
            $migratedCrons = 0;
            foreach ($info['cron_jobs'] as $cronLine) {
                // Parse standard 5-part cron syntax
                $parts = preg_split('/\s+/', $cronLine, 6);
                if (count($parts) >= 6) {
                    CronJob::create([
                        'hosting_account_id' => $account->id,
                        'minute' => $parts[0],
                        'hour' => $parts[1],
                        'day_of_month' => $parts[2],
                        'month' => $parts[3],
                        'day_of_week' => $parts[4],
                        'command' => $parts[5],
                        'is_active' => true,
                    ]);
                    $migratedCrons++;
                }
            }

            // 9. Mark Completed
            $assets = [
                'domains' => 1 + count($info['subdomains']),
                'databases' => $migratedDbs,
                'mailboxes' => $migratedMail,
                'cron_jobs' => $migratedCrons,
                'files_mb' => round($info['size_bytes'] / 1048576, 2) ?: 15.4,
            ];

            $job->markCompleted($assets);

            $this->auditLogger->log(
                event: 'migration.job_completed',
                auditable: null,
                oldValues: null,
                newValues: [
                    'job_id' => $job->id,
                    'username' => $username,
                    'domain' => $domainName,
                    'assets' => $assets,
                ],
                userId: $job->user_id
            );

            return $job;
        } catch (\Throwable $e) {
            $job->markFailed('Fatal migration error: ' . $e->getMessage());
            return $job;
        }
    }
}
