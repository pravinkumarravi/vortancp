# VortanCP Database Schema & Module Ownership Plan

## 1. Schema Design Philosophy

To maintain the architectural boundaries of our Modular Monolith, every table in the VortanCP database is **strictly owned by a single module**.

### 1.1 Ownership Rules
1. **Exclusive Write Access:** Only the owning module's models, actions, and services may execute `INSERT`, `UPDATE`, or `DELETE` operations on its tables.
2. **Cross-Module Reads:** Other modules access data either via public Module Services (recommended) or through read-only Eloquent relationship queries using well-defined contracts.
3. **Foreign Keys & Constraints:** Foreign keys are explicitly defined to guarantee referential integrity at the database engine level (InnoDB / Postgres). Cascading deletes (`CASCADE`) are reserved for child entities (e.g. deleting a DNS zone deletes its records). Parent entities (e.g. deleting a Customer while Subscriptions exist) enforce `RESTRICT` to prevent catastrophic data loss.
4. **Independent Migrations:** Each module maintains its own migration files under `app/Modules/{ModuleName}/Database/Migrations/`. Migrations are registered and executed dynamically by the Module Service Provider.

---

## 2. Complete Module-to-Table Ownership Matrix

| Module Name | Tables Owned | Primary Purpose | Key Foreign Keys & Cascade Rules |
| :--- | :--- | :--- | :--- |
| **Core** | `system_settings`<br>`dashboard_widgets`<br>`announcements` | Global platform settings, per-user widget layouts, broadcast notifications. | `user_id` -> `users.id` (`CASCADE`) |
| **Identity** | `users`<br>`roles`<br>`permissions`<br>`role_user`<br>`permission_role`<br>`two_factor_credentials`<br>`session_histories`<br>`team_invitations` | User credentials, RBAC roles & permissions, MFA credentials, active session tracking. | `user_id` -> `users.id` (`CASCADE`)<br>`role_id` -> `roles.id` (`CASCADE`) |
| **Accounts** | `customers`<br>`hosting_accounts`<br>`account_suspension_histories` | Legal clients, tenant hosting sandboxes, Linux username/UID mappings, status flags. | `customer_id` -> `customers.id` (`RESTRICT`)<br>`server_id` -> `servers.id` (`RESTRICT`) |
| **Resellers** | `resellers`<br>`reseller_resource_allocations`<br>`reseller_brandings` | Reseller profiles, resource pools, white-label themes and logos. | `user_id` -> `users.id` (`CASCADE`)<br>`reseller_id` -> `resellers.id` (`RESTRICT`) |
| **HostingPlans** | `hosting_plans`<br>`hosting_plan_limits`<br>`subscriptions`<br>`subscription_overrides` | Plan templates, resource quotas, subscription bindings, custom overrides. | `customer_id` -> `customers.id` (`RESTRICT`)<br>`hosting_plan_id` -> `hosting_plans.id` (`RESTRICT`) |
| **Servers** | `servers`<br>`server_groups`<br>`server_capabilities`<br>`server_heartbeats` | Node inventory, IP addresses, daemon capabilities, connection keys. | `server_group_id` -> `server_groups.id` (`SET NULL`) |
| **Websites** | `virtual_hosts`<br>`domain_redirects`<br>`custom_error_pages` | Web server vhosts, docroot definitions, rewrite rules, error templates. | `hosting_account_id` -> `hosting_accounts.id` (`CASCADE`)<br>`domain_id` -> `domains.id` (`CASCADE`) |
| **Domains** | `domains`<br>`domain_aliases` | FQDN inventory, primary/addon/subdomain types, ownership records. | `hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **Dns** | `dns_zones`<br>`dns_records`<br>`dns_sec_keys`<br>`dns_zone_templates` | Authoritative DNS zones, resource records (A, MX, TXT, etc.), DNSSEC keys. | `dns_zone_id` -> `dns_zones.id` (`CASCADE`)<br>`hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **Ssl** | `ssl_certificates`<br>`ssl_orders`<br>`acme_challenges` | SSL certificates, private keys (encrypted), ACME order tracking. | `virtual_host_id` -> `virtual_hosts.id` (`CASCADE`)<br>`hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **FileManager** | `file_operations_audits` | History of sensitive web file manager actions (chown, chmod, deletes). | `hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **FileTransfer** | `ftp_accounts`<br>`ssh_keys`<br>`webdav_accounts` | Virtual FTP credentials, SSH authorized keys, WebDAV access. | `hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **Databases** | `database_instances`<br>`database_users`<br>`database_user_grants`<br>`remote_access_hosts` | MySQL/PostgreSQL databases, users, permission mappings, remote IP allowlists. | `hosting_account_id` -> `hosting_accounts.id` (`CASCADE`)<br>`database_id` -> `database_instances.id` (`CASCADE`) |
| **Email** | `mail_domains`<br>`mailboxes`<br>`mail_aliases`<br>`mail_forwarders`<br>`mail_autoresponders`<br>`dkim_keys` | Mail domains, IMAP/POP accounts, Dovecot passwords, forwarders, DKIM secrets. | `mail_domain_id` -> `mail_domains.id` (`CASCADE`)<br>`hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **PhpManager** | `php_runtimes`<br>`php_fpm_pools`<br>`php_directive_overrides` | Installed PHP versions, pool configurations, custom memory/upload limits. | `virtual_host_id` -> `virtual_hosts.id` (`CASCADE`) |
| **Wordpress** | `wordpress_instances`<br>`wordpress_plugins`<br>`wordpress_themes`<br>`wordpress_staging_instances` | WP instances, versions, installed plugins/themes, staging clone relationships. | `virtual_host_id` -> `virtual_hosts.id` (`CASCADE`)<br>`database_id` -> `database_instances.id` (`RESTRICT`) |
| **GitDeployment**| `git_repositories`<br>`git_deployments`<br>`deploy_keys` | Git repos, branch configurations, deployment history logs, webhook secrets. | `virtual_host_id` -> `virtual_hosts.id` (`CASCADE`) |
| **CronJobs** | `cron_jobs`<br>`cron_execution_logs` | Scheduled user tasks, cron expressions, execution history and output. | `hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **Backups** | `backup_configurations`<br>`backup_archives`<br>`backup_destinations`<br>`restore_tasks` | Backup retention rules, archive metadata, S3/SFTP storage keys, restore jobs. | `hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **Monitoring** | `metric_samples`<br>`bandwidth_counters`<br>`alert_thresholds` | System telemetry samples, bandwidth usage per domain/account, alert rules. | `server_id` -> `servers.id` (`CASCADE`)<br>`hosting_account_id` -> `hosting_accounts.id` (`CASCADE`) |
| **Security** | `security_ip_bans`<br>`security_whitelists`<br>`waf_rules`<br>`security_events` | Fail2ban banned IPs, allowlists, ModSecurity rule exclusions, audit events. | `server_id` -> `servers.id` (`CASCADE`) |
| **ServerServices**| `service_definitions`<br>`service_operation_logs` | Managed system daemons, operational restart logs. | `server_id` -> `servers.id` (`CASCADE`) |
| **Networking** | `ip_addresses`<br>`subnet_pools`<br>`ip_assignment_histories` | IPv4/IPv6 address pool, server bindings, PTR reverse DNS records. | `server_id` -> `servers.id` (`RESTRICT`) |
| **SystemAdmin** | `system_package_updates`<br>`system_maintenance_windows` | Pending OS security updates, scheduled maintenance periods. | `server_id` -> `servers.id` (`CASCADE`) |
| **Migrations** | `migration_jobs`<br>`migration_steps`<br>`migration_mappings` | cPanel/Plesk import jobs, step logs, account remapping data. | `target_server_id` -> `servers.id` (`RESTRICT`) |
| **Extensions** | `extension_packages`<br>`extension_settings` | Third-party extensions, metadata, hook registrations. | None (Global) |
| **Api** | `personal_access_tokens`<br>`webhook_subscriptions`<br>`webhook_deliveries` | Sanctum tokens, outbound webhook targets, delivery retry queue. | `user_id` -> `users.id` (`CASCADE`) |
| **Audit** | `audit_logs`<br>`notification_preferences` | Immutable platform audit log, user alert settings. | `user_id` -> `users.id` (`SET NULL`) |
| **Support** | `tickets`<br>`ticket_messages`<br>`invoices`<br>`payments` | Support tickets, customer communication, billing records. | `customer_id` -> `customers.id` (`RESTRICT`) |

---

## 3. Core Relational Diagram (Multitenant Backbone)

```
                  +-------------------+
                  |       users       |
                  +---------+---------+
                            | 1
                            |
                            | 1
                  +---------v---------+
                  |     customers     |
                  +---------+---------+
                            |
                 +----------+----------+
                 | 1                   | 1
                 |                     |
                 v *                   v *
       +-------------------+ +-------------------+
       |   subscriptions   | | hosting_accounts  |
       +---------+---------+ +---------+---------+
                 |                     |
                 |                     | 1
                 |                     +---------------+---------------+---------------+
                 |                                     |               |               |
                 v                                     v *             v *             v *
       +-------------------+                     +-----------+   +-----------+   +-----------+
       |   hosting_plans   |                     |  domains  |   | databases |   | mailboxes |
       +-------------------+                     +-----+-----+   +-----------+   +-----------+
                                                       | 1
                                                       v 1
                                                 +-----------+
                                                 |  vhosts   |
                                                 +-----------+
```

---

## 4. Migration Execution & Autoloading Strategy

The central `Vortan\Core\Providers\CoreServiceProvider` dynamically discovers all module directories during framework boot and registers their migration paths:

```php
foreach ($this->moduleRegistry->getEnabledModules() as $module) {
    $migrationPath = app_path("Modules/{$module->name}/Database/Migrations");
    if (is_dir($migrationPath)) {
        $this->loadMigrationsFrom($migrationPath);
    }
}
```
This guarantees that running `php artisan migrate` executes all migrations across all active modules in proper dependency order without requiring a massive, brittle monolithic migration directory.
