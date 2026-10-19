# Plesk Obsidian Comprehensive Feature Inventory

## 1. Executive Summary & Reference Scope

This document provides a complete inventory of the documented capabilities of **Plesk Obsidian** (Administrator, Reseller, and Customer interfaces), derived from the official Plesk documentation:
- Plesk Administrator Guide: https://docs.plesk.com/en-US/obsidian/administrator-guide/
- Plesk Reseller Guide: https://docs.plesk.com/en-US/obsidian/reseller-guide/
- Plesk Customer Guide: https://docs.plesk.com/en-US/obsidian/customer-guide/
- Plesk REST API & XML-RPC CLI: https://docs.plesk.com/en-US/obsidian/api-rpc/

This inventory highlights Plesk's distinctive architectural approaches—such as multi-subscription hosting, Service Plans, Web Pro/Web Host/Web Admin views, Nginx standalone vs. reverse proxy, Docker extensions, Node.js/Ruby support, WP Toolkit integration, and the Advisor security score—to inform the design of **VortanCP**.

---

## 2. Platform Architecture & Operating System Context

### 2.1 Supported Operating Systems & Runtimes
- **Linux:** AlmaLinux 8/9, Rocky Linux 8/9, RHEL 8/9, Debian 11/12, Ubuntu 20.04/22.04/24.04 LTS.
- **Windows Server:** Windows Server 2019/2022 (IIS, ASP.NET, MSSQL) - *Note: VortanCP is Linux-first; Windows capabilities will be cataloged as optional future adapter extensions.*
- **System Stack:**
  - Web Servers: Apache 2.4, Nginx (both as reverse-proxy cache/static engine and standalone web server via PHP-FPM).
  - PHP Runtimes: Multiple PHP versions (OS vendor, Plesk PHP packages from 7.4 through 8.4) running via FastCGI, PHP-FPM Apache, or PHP-FPM Nginx.
  - Databases: MariaDB / MySQL (local or remote), PostgreSQL, remote Microsoft SQL Server.
  - Mail Stack: Postfix MTA or Qmail, Dovecot or Courier IMAP/POP3, SpamAssassin, Amavis, Plesk Premium Antivirus (Dr.Web).
  - DNS: BIND 9, Slave DNS Manager extension.
  - Security Stack: Fail2ban, ModSecurity (Comodo / OWASP rulesets), Plesk Advisor, SSL It! (ACME client).

### 2.2 Plesk Core Concepts
- **Customer:** An organization or person holding one or more subscriptions.
- **Subscription:** A dedicated hosting unit allocated to a specific system user and assigned resource limits (disk, traffic, domains, mailboxes).
- **Service Plan:** A template defining quotas, hosting parameters (PHP handler, SSL requirements), and permissions.
- **Web Space / Virtual Host Structure:** `/var/www/vhosts/{domain}/httpdocs`.

---

## 3. Comprehensive Feature Inventory

### 3.1 Domains, Websites & Hosting Settings

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Subscription & Webspace Management**<br>Primary unit of resource allocation. Creates isolated Linux system user, home directory, and resource bounds. | [Plesk Subscriptions](https://docs.plesk.com/en-US/obsidian/administrator-guide/subscriptions-and-webspaces/) | Core | Linux system user (`useradd`), filesystem permissions, quota | Admin, Reseller | `subscriptions`, `system_users`, `resource_limits` | Creates system user, `/var/www/vhosts/{domain}` skeleton, sets up PHP-FPM socket, sets disk quota | SuperAdmin / Reseller ACL; strict isolation of system user UID | `Accounts`, `Websites`, `HostingPlans` | Phase 2 |
| **Domains & Subdomains**<br>Supports independent docroots or inherited directories under existing subscriptions. | [Websites and Domains](https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/) | Core | Apache/Nginx vhost configs, BIND/PowerDNS | Customer, Reseller, Admin | `domains`, `dns_zones`, `web_vhosts` | Generates vhost include files, binds PHP-FPM pool, reloads web server | Ownership boundary; subscription limit validation | `Domains`, `Websites` | Phase 2 |
| **Nginx Standalone vs. Apache Reverse Proxy**<br>Toggle between Nginx-only (high performance static + PHP-FPM) and Nginx reverse proxy + Apache backend (for `.htaccess` compatibility). | [Apache and Nginx Settings](https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/web-hosting-settings/apache-and-nginx-settings.html) | Core | Nginx, Apache 2.4 | Customer, Admin | `web_vhost_profiles`, `web_vhosts` | Reconfigures vhost template: either direct `proxy_pass http://127.0.0.1:7080` or direct `fastcgi_pass unix:/var/run/php-fpm/...` | Validation of custom directives; preventing header injection | `Websites`, `PhpManager` | Phase 2 |
| **Web Server Directives & Custom Config**<br>Permits authorized users to append custom Nginx or Apache directives to their virtual hosts. | [Custom Directives](https://docs.plesk.com/en-US/obsidian/administrator-guide/web-servers/apache-and-nginx-settings.html) | Optional / Core | Web server syntax checker (`nginx -t`, `apachectl -t`) | Admin, Customer (if permitted) | `vhost_custom_directives` | Evaluates directives in staging file, performs dry-run syntax check, merges into live config | High-risk feature: strict allowlist/regex parsing to block dangerous directives (`root /;`, `alias /etc/`) | `Websites`, `Security` | Phase 2 |
| **Git Deployment Integration**<br>Pulls remote or local Git repositories, supports branch switching, automated build actions (Composer, npm). | [Git Support](https://docs.plesk.com/en-US/obsidian/customer-guide/git-support/) | Core | Git binary, SSH deploy keys, bash runner | Customer | `git_repositories`, `git_deployments` | Clones repository to target path; executes post-receive shell commands | Commands executed under customer system user jail; secrets masked in logs | `GitDeployment` | Phase 3 |

---

### 3.2 DNS Management (Plesk DNS Server & Slave DNS)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Plesk DNS Zone Editor**<br>Manages master/slave DNS zones, records (A, AAAA, CNAME, MX, NS, TXT, SRV, CAA, PTR, DS), and TTL. | [DNS Zones](https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/dns-and-domain-names/dns-zones.html) | Core | BIND 9 (`named`), rndc | Customer, Reseller, Admin | `dns_zones`, `dns_records` | Writes named zone files, invokes `rndc reload {zone}` | Record format validation, RFC compliance, prevents loop and open-recursion | `Dns` | Phase 2 |
| **DNSSEC Extension**<br>Automated signing of DNS zones, rollover of KSK and ZSK cryptographic keys, DS record export. | [DNSSEC](https://docs.plesk.com/en-US/obsidian/administrator-guide/domain-names-and-dns/dnssec.html) | Core Extension | OpenSSL, BIND DNSSEC tools | Customer, Admin | `dns_sec_keys` | Signs zone file using `dnssec-signzone`, schedules key rollover cron | Protects private keys; rate-limits signing operations | `Dns` | Phase 2 |
| **Slave DNS Manager**<br>Integrates with external secondary nameservers (cPanel DNSOnly, PowerDNS, standard BIND) via RFC 1995 IXFR / AXFR. | [Slave DNS](https://docs.plesk.com/en-US/obsidian/administrator-guide/domain-names-and-dns/slave-dns-server.html) | Optional Extension | BIND zone transfer ACLs, TSIG keys | Admin | `dns_slave_servers` | Configures `allow-transfer` directives, TSIG transaction security keys | Cryptographic TSIG key protection; restricted transfer IPs | `Dns`, `Servers` | Phase 2 |

---

### 3.3 SSL/TLS & Security (SSL It! & Advisor)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **SSL It! (Automated Certificate Management)**<br>Multi-CA ACME certificate management (Let's Encrypt, DigiCert, Sectigo), wildcard cert support via DNS-01. | [SSL It! Extension](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/securing-with-ssl-tls/ssl-it.html) | Core Extension | ACME client, DNS API adapters, OpenSSL | Customer, Admin | `ssl_certificates`, `ssl_orders`, `ssl_challenges` | Places ACME HTTP-01 challenge or injects DNS-01 TXT record; installs cert on web/mail/FTP | Secure private key permissions (0600 root:root); automatic renewal daemon | `Ssl`, `Websites`, `Dns` | Phase 2 |
| **Plesk Advisor**<br>Automated security score and auditor; inspects firewall, outdated software, SSL coverage, and password strength. | [Advisor](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/advisor.html) | Core Extension | System inspection scripts, vulnerability database | Admin | `security_audit_reports`, `security_recommendations` | Scans server configuration, checks port exposure, audits SSL ciphers | Read-only inspection; non-disruptive security analysis | `Security`, `Monitoring` | Phase 3 |
| **Fail2ban Integration**<br>Scans log files (SSH, Apache, Nginx, Postfix, Dovecot, Plesk Panel) and bans offensive IPs via iptables/firewalld. | [Protection Against Brute Force (Fail2ban)](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/fail2ban.html) | Core | `fail2ban-client`, `fail2ban-server`, iptables | Admin | `security_ip_bans`, `fail2ban_jails` | Modifies `jail.local`, invokes `fail2ban-client set {jail} banip {ip}` | Whitelist protection for management IPs; rate limit adjustments | `Security` | Phase 3 |
| **ModSecurity Web Application Firewall**<br>Real-time HTTP traffic inspection, SQLi/XSS blocking via OWASP or Atomic rulesets. | [Web Application Firewall (ModSecurity)](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/modsecurity.html) | Core | `libmodsecurity3` / `mod_security2` | Admin, Customer | `waf_rulesets`, `waf_domain_exceptions` | Downloads updated CRS rulesets, applies domain exclusions to web server config | Dry-run detection mode ("Detection Only") before enforcing blocking ("On") | `Security`, `Websites` | Phase 3 |

---

### 3.4 File & Data Transfer Management

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Plesk File Manager**<br>Visual file manager with integrated code editor, search, archive extraction, permission calculator, and image preview. | [File Manager](https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/file-manager/) | Core | Linux FS, `tar`, `unzip`, `chown`, `chmod` | Customer | `file_operations_audit` | Reads/writes files strictly within `/var/www/vhosts/{domain}` | Traversal block, symlink escape checks, customer user permission restriction | `FileManager` | Phase 2 |
| **FTP & Chrooted Shell Accounts**<br>Multiple FTP accounts per subscription, with choice of system user shell or chrooted shell (`/bin/rbash`). | [FTP Access](https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/web-hosting-settings/ftp-access.html) | Core | ProFTPD, Linux PAM, `chroot` jail | Customer | `ftp_accounts`, `system_users` | Updates ProFTPD user authentication, creates chroot home directories | Cryptographic password hashing, restricted directory paths, jailed shell binaries | `FileTransfer`, `Security` | Phase 2 |

---

### 3.5 Database Management

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Multi-Engine Database Support**<br>Manages local and remote MySQL/MariaDB and PostgreSQL database servers. | [Databases](https://docs.plesk.com/en-US/obsidian/customer-guide/databases/) | Core | MariaDB, PostgreSQL client/server | Customer, Admin | `database_servers`, `databases`, `database_users` | Executes database creation and user grant commands via administrative adapter | Password complexity; enforces subscription database count & size quotas | `Databases` | Phase 2 |
| **Webadmin SSO (phpMyAdmin / phpPgAdmin)**<br>One-click browser single sign-on into phpMyAdmin or phpPgAdmin without credential re-entry. | [Managing Databases with phpMyAdmin](https://docs.plesk.com/en-US/obsidian/customer-guide/databases/managing-databases-with-phpmyadmin.html) | Core | phpMyAdmin, phpPgAdmin, temporary session tokens | Customer | `database_sso_tokens` | Authenticates via temporary session ticket directly to admin tool | Token valid for 60 seconds; restricted to single client IP and single database | `Databases` | Phase 2 |
| **Database Dump Export / Import**<br>Web-based export to `.sql` / `.sql.zip` and import with integrity checks. | [Exporting and Importing Databases](https://docs.plesk.com/en-US/obsidian/customer-guide/databases/exporting-and-importing-databases.html) | Core | `mysqldump`, `pg_dump`, `mysql`, `psql` | Customer | `backup_items` | Streams dump execution via non-blocking queue job | Prevents shell argument injection; temporary dump files cleaned up | `Databases`, `Backups` | Phase 2 |

---

### 3.6 Email System Management

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Mail Account & Mailbox Administration**<br>IMAP/POP3 mailbox creation, storage quotas, password policies, aliases, forwarders, autoresponders. | [Mail Settings](https://docs.plesk.com/en-US/obsidian/customer-guide/mail/) | Core | Postfix, Dovecot, Linux filesystem | Customer | `mail_domains`, `mailboxes`, `mail_aliases`, `mail_forwarders` | Creates mail storage paths, updates Postfix virtual maps and Dovecot auth tables | Strong password hashing; mailbox quota verification; TLS encryption | `Email` | Phase 2 |
| **DKIM, SPF, and DMARC Protection**<br>One-click DKIM signing activation, automated SPF string composition, DMARC reporting policies. | [DKIM, SPF, and DMARC Protection](https://docs.plesk.com/en-US/obsidian/administrator-guide/mail/antispam-tools/dkim-spf-and-dmarc-protection.html) | Core | OpenSSL, Postfix milter, DNS | Customer, Admin | `mail_dkim_keys`, `dns_records` | Generates 2048-bit DKIM private key, updates milter table, adds TXT record | Secure private key storage; syntax validation of SPF and DMARC | `Email`, `Dns` | Phase 2 |
| **Outbound Mail Limiting (Antispam)**<br>Limits outbound messages per hour per mailbox, domain, and subscription to prevent IP blacklisting. | [Limitations on Outgoing Mail](https://docs.plesk.com/en-US/obsidian/administrator-guide/mail/limitations-on-outgoing-mail.html) | Core | Postfix policy service, SQLite/Redis counter | Admin, Reseller | `mail_rate_limits`, `mail_rate_logs` | Rejects SMTP `DATA` commands exceeding hourly threshold; alerts administrator | Prevents compromised scripts from sending high-volume spam | `Email`, `Security` | Phase 2 |
| **Webmail (Roundcube / Horde)**<br>Webmail client access with SSL and automatic configuration autodiscover/autoconfig profiles. | [Webmail](https://docs.plesk.com/en-US/obsidian/customer-guide/mail/using-webmail.html) | Core | Roundcube webmail, XML/JSON autodiscover endpoints | Customer | `webmail_profiles` | Serves standard Mozilla/Microsoft XML autoconfig endpoints for mobile email clients | Secure cookie flags; rate limits webmail login endpoint | `Email` | Phase 2 |

---

### 3.7 PHP & Runtime Management

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **PHP Handler Selector**<br>Configures PHP handler per website: FPM by Apache, FPM by Nginx, or FastCGI. | [PHP Settings](https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/web-hosting-settings/php-settings.html) | Core | PHP-FPM daemons (7.4, 8.0, 8.1, 8.2, 8.3, 8.4) | Customer, Admin | `php_handlers`, `website_php_settings` | Writes dedicated FPM pool configuration file; reloads respective PHP-FPM service | Pool runs under tenant UID/GID; `open_basedir` enabled by default | `PhpManager`, `Websites` | Phase 2 |
| **PHP Directives & Performance Settings**<br>Adjusts memory limit, max execution time, opcache memory, error reporting per virtual host. | [PHP Configuration Directives](https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/web-hosting-settings/php-settings.html#custom-php-directives) | Core | `.user.ini` or FPM pool configuration | Customer | `website_php_directives` | Updates pool `php_value` directives or docroot `.user.ini` file | Hard system ceilings prevent excessive memory allocation | `PhpManager`, `HostingPlans` | Phase 2 |
| **Node.js, Python & Ruby Support (Plesk Phusion Passenger)**<br>Runs Node.js, Python (WSGI), or Ruby applications via Phusion Passenger or reverse proxy. | [Node.js Support](https://docs.plesk.com/en-US/obsidian/customer-guide/nodejs-support/) | Optional Extension | Node.js versions, npm, Passenger | Customer | `application_instances`, `app_runtimes` | Creates Passenger config snippet or sets up systemd user service + Nginx reverse proxy | Application runs under customer system user UID; port binding isolation | `ApplicationManager` | Phase 3 |

---

### 3.8 WordPress Toolkit (Plesk Obsidian First-Party)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **1-Click WordPress Installation**<br>Provisions new WordPress installation with automated DB creation, strong admin credentials, and caching plugin. | [WordPress Toolkit](https://docs.plesk.com/en-US/obsidian/customer-guide/wordpress-toolkit.html) | Core Extension | WP-CLI, PHP runtime, MySQL | Customer, Admin | `wp_instances`, `wp_plugins`, `wp_themes` | Downloads core files, creates DB/user, writes `wp-config.php`, executes `wp core install` | File permissions set to tenant user; randomized table prefix; strong passwords | `Wordpress`, `Databases` | Phase 3 |
| **WordPress Staging & 1-Click Sync**<br>Clones production WordPress site to staging subdomain; synchronizes files and databases back to production with URL replacement. | [Cloning and Staging](https://docs.plesk.com/en-US/obsidian/customer-guide/wordpress-toolkit/cloning-a-wordpress-site.html) | Core Extension | WP-CLI `search-replace`, rsync | Customer | `wp_staging_instances`, `wp_sync_jobs` | Clones filesystem, duplicates DB, executes serialized `wp search-replace` | Preflight database backup before sync; verifies docroot boundaries | `Wordpress`, `Backups` | Phase 3 |
| **Smart Updates & Security Auditing**<br>AI/visual regression testing of updates; vulnerability scanner for plugins and themes. | [Smart Updates](https://docs.plesk.com/en-US/obsidian/customer-guide/wordpress-toolkit/smart-updates.html) | Paid Extension / Premium | Headless browser (Puppeteer / Playwright), WP vulnerability DB | Customer, Admin | `wp_update_checks`, `wp_security_audits` | Clones to temporary sandbox, updates plugin, compares screenshots, applies if safe | Sandboxed execution; external vulnerability API rate limiting | `Wordpress`, `Security` | Phase 3 |

---

### 3.9 Backup & Recovery (Plesk Backup Manager)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Granular Subscription & Server Backup**<br>Full, differential, and incremental backups of subscriptions, customer accounts, or entire server. | [Backup Manager](https://docs.plesk.com/en-US/obsidian/customer-guide/backup-manager.html) | Core | `tar`, `gzip`, OpenSSL encryption, `mysqldump` | Customer, Reseller, Admin | `backup_configurations`, `backup_tasks`, `backup_archives` | Generates structured archive containing metadata XML, SQL dumps, and web content | Optional password encryption (AES-256-CBC); verifies file integrity via SHA256 checksums | `Backups` | Phase 3 |
| **Selective Restoration**<br>Restores specific items from backup archive: single database, email account, DNS zone, or individual directory. | [Restoring Data](https://docs.plesk.com/en-US/obsidian/customer-guide/backup-manager/restoring-data.html) | Core | Streaming tar extractor, MySQL client | Customer, Admin | `restore_jobs`, `restore_preflight_checks` | Extracts chosen artifact from archive without overwriting entire subscription | Preflight check verifies target account ownership; safety backup before restore | `Backups` | Phase 3 |
| **Cloud Storage Integration**<br>Direct backup upload and retrieval to Amazon S3, Google Drive, Microsoft OneDrive, Dropbox, SFTP. | [Remote Backup Storage](https://docs.plesk.com/en-US/obsidian/administrator-guide/backup-and-restoration/remote-backup-storage.html) | Optional Extension | Cloud SDKs, OAuth2 refresh tokens, Flysystem | Customer, Admin | `backup_destinations` | Streams backup chunks to cloud storage API endpoints | OAuth tokens and secret keys stored in encrypted credentials store | `Backups` | Phase 3 |

---

### 3.10 Service Plans, Resellers & Subscriptions

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Service Plans & Add-on Plans**<br>Defines disk, traffic, domain, mailbox quotas, PHP handler restrictions, and feature toggles. | [Service Plans](https://docs.plesk.com/en-US/obsidian/administrator-guide/service-plans-and-subscriptions/service-plans.html) | Core | Database configuration | Admin, Reseller | `hosting_plans`, `plan_entitlements`, `addon_plans` | Enforces plan limits upon subscription creation; syncs changes to child subscriptions | Reseller plans cannot allocate resources exceeding reseller pool limits | `HostingPlans`, `Resellers` | Phase 2 |
| **Plan Synchronization & Overrides**<br>Tracks whether a subscription is synchronized with its parent Service Plan or has custom overrides. | [Syncing Subscriptions](https://docs.plesk.com/en-US/obsidian/administrator-guide/service-plans-and-subscriptions/subscriptions/syncing-subscriptions.html) | Core | Database state tracking | Admin, Reseller | `subscriptions` (is_synced flag, custom_overrides JSON) | Re-applies plan changes across all synchronized subscriptions | Audit logged; overrides require explicit administrative privilege | `HostingPlans` | Phase 2 |
| **Reseller Multi-Tenancy Hierarchy**<br>Resellers own customers and subscriptions; manage packages and allocate assigned disk/traffic quotas. | [Reseller Accounts](https://docs.plesk.com/en-US/obsidian/reseller-guide/reseller-accounts/) | Core | Multi-tenant hierarchy enforcement | Admin, Reseller | `resellers`, `reseller_limits`, `customers` | Restricts queries to current reseller's organizational subtree | Cross-tenant queries strictly prohibited in all Eloquent scopes and actions | `Resellers`, `Identity` | Phase 2 |

---

### 3.11 Monitoring, Health & Performance

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Plesk Health Monitor**<br>Real-time and historical graphs of CPU, RAM, disk I/O, swap, network traffic, and daemon status. | [Health Monitor](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/health-monitor.html) | Core | System metrics collector, RRDtool / Timescale / SQLite | Admin | `metric_samples_hourly`, `metric_threshold_alerts` | Samples `/proc` and `sysstat` at 1-minute intervals; triggers notification on threshold breach | Read-only; rate limited data aggregation | `Monitoring`, `Servers` | Phase 3 |
| **Action Log & Audit Trail**<br>Comprehensive, immutable log recording every administrative action, user, timestamp, IP, and outcome. | [Action Log](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/action-log.html) | Core | Database audit table | Admin | `audit_logs` | Writes structured audit entries on every mutation event | Write-only / immutable log table; sensitive values (passwords, keys) redacted | `Audit`, `Identity` | Phase 1 |

---

## 4. Plesk API Ecosystem Summary
Plesk supports two API paradigms:
1. **Plesk REST API (v2):** Modern JSON REST API (`/api/v2/domains`, `/api/v2/clients`, `/api/v2/cli/database`) authenticated via API Keys (`X-API-Key`).
2. **Plesk XML-RPC / CLI:** Legacy XML interface and command-line management utilities (`plesk bin domain`, `plesk bin subscription`).

**Key Architecture Takeaway for VortanCP:**
Plesk's distinction between a **Customer** (Identity/Legal Entity) and a **Subscription** (Tenant Hosting Sandbox) is architecturally superior to cPanel's 1:1 coupling of user and account. VortanCP will adopt the Customer -> Multiple Subscriptions hierarchy, allowing one client to hold multiple independent web hosting packages, each running under its own isolated system user.
