# cPanel & WHM Comprehensive Feature Inventory

## 1. Executive Summary & Reference Scope

This document provides an exhaustive inventory of the documented capabilities of **cPanel** (Customer/End-user Control Panel) and **WHM** (Web Host Manager - Administrative/Reseller Interface), derived from the official cPanel documentation:
- cPanel User Documentation: https://docs.cpanel.net/cpanel/
- WHM Documentation: https://docs.cpanel.net/whm/
- cPanel & WHM Developer & API Documentation: https://api.docs.cpanel.net/

The purpose of this inventory is to establish a rigorous baseline for our modular hosting management platform (**VortanCP**), mapping every documented feature, server dependency, operating system constraint, security requirement, API/UAPI endpoint, and system service integration.

---

## 2. Platform Architecture & Operating System Context

### 2.1 Supported Operating Systems & Runtimes
- **Tier 1 Platforms:** AlmaLinux 8/9, Rocky Linux 8/9, CloudLinux 8/9, Ubuntu 20.04/22.04 LTS.
- **Runtimes & Daemon Stack:**
  - Web: Apache 2.4 (via EasyApache 4), Nginx (reverse proxy or standalone).
  - PHP: Multiple PHP versions (ea-php74 through ea-php84) managed via PHP-FPM, suPHP, or mod_lsapi (CloudLinux).
  - Databases: MySQL 8.0, MariaDB 10.6/10.11.
  - Mail Transport: Exim MTA, Dovecot IMAP/POP3, SpamAssassin, Mailman (legacy/deprecated).
  - DNS: BIND 9, PowerDNS, NSD.
  - FTP/SFTP: Pure-FTPd, ProFTPD, OpenSSH.
  - Security/Isolation: CageFS (CloudLinux), mod_security, cphulkd (brute force protection), iptables/nftables, Firewalld.

### 2.2 System Architecture Paradigm
cPanel/WHM operates as a deeply coupled, single-node or clustered Linux control plane. WHM runs on privileged ports `2086` (HTTP) and `2087` (HTTPS) executed as `root`. cPanel runs on ports `2082` (HTTP) and `2083` (HTTPS) under system user privileges via internal setuid wrappers (`cpsrvd`). Communication occurs via internal UNIX sockets and private IPC pipes.

---

## 3. Comprehensive Feature Inventory

The inventory is grouped into functional domains representing cPanel & WHM core systems.

### 3.1 Domain & Web Hosting Management

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Primary Domain Management**<br>Assigns main domain, sets document root (`/public_html`), user home directory, and user UID/GID. | [cPanel Domains](https://docs.cpanel.net/cpanel/domains/domains/) | Core | Linux FS, Apache/Nginx, useradd/chown | Admin, Reseller, Customer | `hosting_accounts`, `domains`, `web_vhosts` | Linux user creation, docroot creation, vhost generation, reload web server | Root privileges on server; isolated customer UID; quota check | `Accounts`, `Websites`, `Servers` | Phase 2 |
| **Addon Domains & Subdomains**<br>Hosts multiple independent websites within single hosting subscription with isolated document roots. | [cPanel Subdomains](https://docs.cpanel.net/cpanel/domains/subdomains/) | Core | Web server vhost configuration, BIND/PowerDNS | Customer | `domains`, `dns_zones`, `dns_records` | Generate vhost snippet, symlink or directory provisioning, local DNS zone record injection | Customer ownership check, domain entitlement limit verification | `Domains`, `Websites`, `Dns` | Phase 2 |
| **Domain Aliases (Parked Domains)**<br>Points alternate domains directly to the primary site docroot without separate content. | [cPanel Aliases](https://docs.cpanel.net/cpanel/domains/aliases/) | Core | Web server ServerAlias directive, DNS zone | Customer | `domains` (type=alias) | Append `ServerAlias` to web server configuration; create DNS zone | Customer tenancy check; alias quota enforcement | `Domains`, `Websites`, `Dns` | Phase 2 |
| **URL Redirects (301/302)**<br>Permits permanent/temporary redirection of paths or entire domains with regex and www matching options. | [cPanel Redirects](https://docs.cpanel.net/cpanel/domains/redirects/) | Core | Apache `.htaccess` or Nginx rewrite rules | Customer | `domain_redirects` | Modifies `.htaccess` or re-renders Nginx vhost include file | Path validation to prevent open-redirect / SSRF attacks; chroot boundary | `Websites`, `Domains` | Phase 2 |
| **Custom Error Pages**<br>Configures bespoke response pages for 400, 401, 403, 404, 500 status codes. | [Custom Error Pages](https://docs.cpanel.net/cpanel/advanced/error-pages/) | Core | Web server error directives, static HTML files | Customer | `website_error_pages` | Writes custom HTML files in docroot and adds `ErrorDocument` directives | File write permission within docroot; XSS sanitization of templates | `Websites` | Phase 2 |
| **Directory Indexing & Indexes Manager**<br>Controls auto-indexing, FancyIndexing, or directory listing prohibition. | [cPanel Indexes](https://docs.cpanel.net/cpanel/advanced/indexes/) | Core | Web server directory options (`Options +/-Indexes`) | Customer | `website_directory_options` | Updates `.htaccess` or web server config block | Restricted to customer docroot | `Websites` | Phase 2 |
| **Web Server Access & Error Logs**<br>Real-time viewing and download of Apache/Nginx HTTP access and error logs. | [Raw Access Logs](https://docs.cpanel.net/cpanel/metrics/raw-access/) | Core | Linux file streaming, logrotate, gzip | Customer | `audit_logs` (read tracking) | Streams parsed lines from `/var/log/nginx/domains/{domain}.log` | Safe read; no access to other tenants' log pipes | `Logs`, `Websites` | Phase 3 |

---

### 3.2 DNS Management (cPanel Zone Editor & WHM DNS Manager)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Authoritative DNS Zone Management**<br>Create, edit, delete zone files (SOA, NS, A, AAAA, CNAME, MX, TXT, SRV, CAA, PTR). | [Zone Editor](https://docs.cpanel.net/cpanel/domains/zone-editor/) | Core | BIND 9 / PowerDNS / NSD | Admin, Reseller, Customer | `dns_zones`, `dns_records` | Writes named zone files or executes PowerDNS SQL/API mutations; serial bumping | Strict record validation (prevent zone poison, CNAME collision, invalid TTL) | `Dns` | Phase 2 |
| **DNSSEC (Domain Name System Security Extensions)**<br>Generates KSK/ZSK keypairs, DS records, and cryptographic signatures. | [cPanel DNSSEC](https://docs.cpanel.net/cpanel/domains/dnssec/) | Optional/Core | `dnssec-keygen`, `dnssec-signzone`, PowerDNS DNSSEC engine | Customer, Admin | `dns_sec_keys` | Invokes DNSSEC key generator; produces DS records for parent registrar | Protection of private key material; rate limiting key rotations | `Dns` | Phase 2 |
| **DNS Clustering (WHM)**<br>Synchronizes zone records across a distributed cluster of dedicated DNS nodes (standalone nameservers). | [WHM DNS Cluster](https://docs.cpanel.net/whm/dns/dns-cluster/) | Core WHM | cPanel DNSOnly daemon, SSH/API sync | Admin | `dns_clusters`, `dns_cluster_nodes` | Broadcasts zone updates to remote DNS nodes over HTTPS or custom protocol | Mutual TLS or HMAC-authenticated API tokens between master and slaves | `Dns`, `Servers` | Phase 2 |
| **Zone Templates**<br>Customizes initial default DNS records generated upon domain/account creation. | [Edit Zone Templates](https://docs.cpanel.net/whm/dns/edit-zone-templates/) | Core WHM | File templates, placeholder substitution | Admin | `dns_zone_templates` | Evaluates dynamic placeholders (`%ip%`, `%domain%`, `%nameserver%`) | Admin only; template injection sanitization | `Dns` | Phase 2 |

---

### 3.3 SSL/TLS Certificate Management (AutoSSL & SSL Manager)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **AutoSSL (Automated ACME/Let's Encrypt)**<br>Automated issuance, verification (HTTP-01/DNS-01), renewal, and web server installation of SSL certs. | [WHM AutoSSL](https://docs.cpanel.net/whm/ssl-tls/manage-autossl/) | Core | ACME Client (Certbot / acme.sh / native PHP client), OpenSSL | Customer, Admin | `ssl_certificates`, `ssl_orders`, `acme_challenges` | Places challenge tokens in `/.well-known/acme-challenge/`, calls ACME CA, updates vhost SSL directives, reloads web server | Safe key storage (4096-bit RSA or ECDSA P-256/384); private key access restricted to root/daemon | `Ssl`, `Websites`, `Dns` | Phase 2 |
| **Manual SSL Certificate & Key Installation**<br>Upload external commercial certificates, private keys, intermediate CA bundles. | [cPanel SSL/TLS](https://docs.cpanel.net/cpanel/security/ssl-tls/) | Core | OpenSSL validation (`openssl x509`, `openssl rsa`) | Customer, Admin | `ssl_certificates` | Validates modulus match between CRT and KEY; bundles CA chain; writes to `/etc/ssl/` | Modulus verification, strict CSRF, prevents uploading malicious files as certs | `Ssl`, `Websites` | Phase 2 |
| **Certificate Signing Request (CSR) Generator**<br>Generates 2048/4096-bit private keys and CSRs conforming to X.509 RFC standards. | [Generate CSR](https://docs.cpanel.net/cpanel/security/ssl-tls/#certificate-signing-requests-csr) | Core | OpenSSL binary / PHP OpenSSL extension | Customer | `ssl_csrs` | Executes `openssl req -new` with custom SANs | Prevents command injection in subject fields (CN, O, C, ST, L) | `Ssl` | Phase 2 |
| **Force HTTPS Redirection**<br>Automated web server rewrite rule redirecting port 80 to port 443 with HSTS options. | [Force HTTPS](https://docs.cpanel.net/cpanel/domains/domains/#force-https-redirect) | Core | Apache/Nginx vhost configuration | Customer | `web_vhosts` (force_ssl boolean) | Adds HTTPS redirect block to server configuration | Prevents infinite redirect loops; ensures certificate exists before forcing | `Websites`, `Ssl` | Phase 2 |

---

### 3.4 File & Data Transfer Management

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Web File Manager**<br>Interactive web interface for directory browsing, file editing, uploads, chmod, zip/unzip, and file search. | [cPanel File Manager](https://docs.cpanel.net/cpanel/files/file-manager/) | Core | Linux FS, zip/unzip, tar, fileinfo | Customer | `file_operations_audit` | Reads/writes files strictly under tenant root (`/home/{user}`); permission modifications | Strict path traversal prevention (`realpath` check, null-byte filter, chroot jail); max file size limits | `FileManager` | Phase 2 |
| **FTP/FTPS Account Management**<br>Creates virtual FTP users mapped to specific subdirectories with quota constraints. | [cPanel FTP Accounts](https://docs.cpanel.net/cpanel/files/ftp-accounts/) | Core | Pure-FTPd / ProFTPD (with virtual user database or PAM) | Customer | `ftp_accounts` | Updates pure-ftpd virtual DB or `/etc/pure-ftpd/passwd` | Dedicated passwords hashed with Argon2id/SHA-512; directory confinement | `FileTransfer` | Phase 2 |
| **SSH Access & Public Key Management**<br>Manages authorized SSH keys, jail shell (`jailshell`), and full shell access toggles. | [cPanel SSH Access](https://docs.cpanel.net/cpanel/security/ssh-access/) | Core | OpenSSH (`authorized_keys`), chroot jail/systemd slice | Customer, Admin | `ssh_keys` | Appends public keys to `/home/{user}/.ssh/authorized_keys`; configures shell in `/etc/passwd` | Shell access disabled by default; strict SSH key format validation; privilege isolation | `FileTransfer`, `Security` | Phase 2 |
| **Web Disk (WebDAV)**<br>Provides WebDAV access to user files over HTTP/HTTPS for mounting in Windows Explorer or macOS Finder. | [cPanel Web Disk](https://docs.cpanel.net/cpanel/files/web-disk/) | Core | Apache `mod_dav` / Nginx DAV module, Digest auth | Customer | `webdav_accounts` | Writes DAV configuration and htpasswd entries | Enforces TLS; path confinement | `FileTransfer` | Phase 2 |

---

### 3.5 Database Management (MySQL, MariaDB, PostgreSQL)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Database & User Provisioning**<br>Creates prefix-scoped databases (`user_dbname`) and users (`user_dbuser`) with granular grants. | [cPanel MySQL Databases](https://docs.cpanel.net/cpanel/databases/mysql-databases/) | Core | MySQL 8.0+ / MariaDB 10.6+ | Customer | `databases`, `database_users`, `database_user_grants` | Executes `CREATE DATABASE`, `CREATE USER`, `GRANT ... ON db.* TO user` via root DB socket | Mandatory prefix isolation; password complexity; least-privilege administrative connector | `Databases` | Phase 2 |
| **Remote MySQL Access Hosts**<br>Allows external client IP addresses or subnets (`%`, `1.2.3.4`) to connect to tenant databases. | [Remote MySQL](https://docs.cpanel.net/cpanel/databases/remote-mysql/) | Core | MySQL grant tables, iptables / host allowlists | Customer | `database_remote_hosts` | Updates MySQL user host definitions (`'user'@'1.2.3.4'`) | Restricts wildcards (`%`) unless explicitly permitted by admin; firewall port 3306 binding | `Databases`, `Security` | Phase 2 |
| **phpMyAdmin Integration**<br>One-click single-sign-on (SSO) into web-based database administration tool. | [phpMyAdmin](https://docs.cpanel.net/cpanel/databases/phpmyadmin/) | Core | phpMyAdmin, PHP-FPM, temporary SSO token table | Customer | `database_sso_tokens` | Generates short-lived HMAC SSO session and redirects customer to phpMyAdmin endpoint | SSO token expiration (60s); prevents accessing other users' databases; secure cookie flags | `Databases` | Phase 2 |
| **Database Backup & Dump (mysqldump)**<br>On-demand generation and restoration of `.sql` or `.sql.gz` dump files. | [Database Backup](https://docs.cpanel.net/cpanel/files/backup/) | Core | `mysqldump`, `mysql`, gzip | Customer | `backup_items` | Invokes streaming `mysqldump` subprocess with credentials passed via secure options file | Sensitive credentials never exposed in process args (`--defaults-file`); output size quota check | `Databases`, `Backups` | Phase 2 |

---

### 3.6 Email System Management

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Mailbox Provisioning & Quota**<br>Creates IMAP/POP3 mailboxes with per-account storage quotas, passwords, and Maildir paths. | [Email Accounts](https://docs.cpanel.net/cpanel/email/email-accounts/) | Core | Dovecot, Exim, Linux Maildir | Customer | `mail_domains`, `mailboxes` | Creates filesystem structure in `/home/{user}/mail/{domain}/{user}`, writes Dovecot passdb/userdb | Password hashed with SHA512-CRYPT / Argon2id; quota enforcement in Dovecot; path isolation | `Email`, `Accounts` | Phase 2 |
| **Email Forwarders & Aliases**<br>Forwards incoming mail from one address to another internal or external recipient. | [Forwarders](https://docs.cpanel.net/cpanel/email/forwarders/) | Core | Exim aliases file / database lookup | Customer | `mail_forwarders` | Updates Exim alias configuration or virtual forward table | Loops detection; outbound rate limiting to prevent spam forwarding reputation damage | `Email` | Phase 2 |
| **Autoresponders**<br>Automated vacation or acknowledgment replies with character set and interval controls. | [Autoresponders](https://docs.cpanel.net/cpanel/email/autoresponders/) | Core | Exim autoresponder transport, SQLite/file tracker | Customer | `mail_autoresponders` | Configures autoresponder script and tracking db to prevent reply loops | Throttles responses per sender (e.g. 1 reply per 8 hours); HTML sanitization | `Email` | Phase 2 |
| **Email Deliverability (SPF, DKIM, DMARC)**<br>Automated private/public key generation for DKIM, SPF record composition, and DMARC record builder. | [Email Deliverability](https://docs.cpanel.net/cpanel/email/email-deliverability/) | Core | OpenSSL (DKIM keys), Exim DKIM signing, DNS | Customer, Admin | `mail_dkim_keys`, `dns_records` | Generates 2048-bit RSA DKIM keypair, writes private key to secure directory, publishes public key to DNS | Private key accessible only to Exim daemon; strict DNS syntax validation | `Email`, `Dns` | Phase 2 |
| **SpamAssassin & Greylisting**<br>Configures spam score thresholds, auto-delete, whitelist/blacklist, and Postgrey delay daemon. | [Spam Filters](https://docs.cpanel.net/cpanel/email/spam-filters/) | Core | SpamAssassin (`spamd`), Postgrey, Exim ACLs | Customer, Admin | `mail_spam_filters`, `mail_greylisting_rules` | Modifies user `.spamassassin/user_prefs` and greylist exception database | Input validation; limits rule expression complexity to avoid ReDoS | `Email`, `Security` | Phase 2 |
| **Webmail Access (Roundcube SSO)**<br>Single-sign-on access to webmail interface without entering mailbox password again. | [Webmail](https://docs.cpanel.net/cpanel/email/webmail/) | Core | Roundcube webmail application, Dovecot SASL | Customer | `webmail_sessions` | Generates authenticated ephemeral token for Roundcube session | Cookie domain confinement, session timeouts, CSRF tokens | `Email` | Phase 2 |

---

### 3.7 PHP & Application Runtime Management (EasyApache 4 / MultiPHP)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **MultiPHP Manager**<br>Selects PHP version (e.g. 8.1, 8.2, 8.3, 8.4) independently per virtual host / directory. | [MultiPHP Manager](https://docs.cpanel.net/whm/software/multiphp-manager/) | Core | PHP-FPM pools, Apache `mod_proxy_fcgi` | Customer, Admin | `php_versions`, `website_php_settings` | Generates PHP-FPM pool configuration file (`/etc/php-fpm.d/{user}.conf`), reloads PHP-FPM | User pool runs under customer UID/GID; forbids user modifying base binary paths | `PhpManager`, `Websites` | Phase 2 |
| **MultiPHP INI Editor**<br>Configures directives such as `memory_limit`, `upload_max_filesize`, `max_execution_time`, `display_errors`. | [MultiPHP INI Editor](https://docs.cpanel.net/cpanel/software/multiphp-ini-editor/) | Core | `php.ini` or `.user.ini` file in docroot | Customer | `website_php_directives` | Writes `.user.ini` or updates FPM pool `php_admin_value` directives | Server-enforced upper bounds (e.g. cannot exceed max memory_limit set by hosting plan) | `PhpManager`, `HostingPlans` | Phase 2 |
| **PHP Extension & PECL Management**<br>Enables/disables extensions (opcache, redis, imagick, pdo_mysql, intl, etc.). | [EasyApache 4](https://docs.cpanel.net/whm/software/easyapache-4/) | Core WHM | Package manager (dnf/apt), PECL | Admin | `php_extensions` | Invokes system package manager or compiles extension for specific PHP version | Admin only; verifies package signature | `PhpManager`, `ServerServices` | Phase 3 |

---

### 3.8 Cron Jobs & Task Automation

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Scheduled Tasks (Cron Jobs)**<br>Configures recurring tasks with standard 5-part cron syntax, predefined presets, and email output logging. | [cPanel Cron Jobs](https://docs.cpanel.net/cpanel/advanced/cron-jobs/) | Core | Linux Vixie Cron / Crond (`/var/spool/cron/{user}`) | Customer | `cron_jobs`, `cron_job_logs` | Writes to customer's user crontab file using `crontab -u {user}` | Syntax validation; command execution restricted to user shell/jail; timeout and nice controls | `CronJobs`, `Security` | Phase 3 |

---

### 3.9 Backup & Disaster Recovery

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **WHM Backup Configuration**<br>Automated full server and account backups; daily, weekly, monthly retention schedules. | [WHM Backup Configuration](https://docs.cpanel.net/whm/backup/backup-configuration/) | Core WHM | `tar`, `gzip`, `pigz`, rsync, rclone | Admin | `backup_configurations`, `backup_jobs` | Orchestrates asynchronous archiving of `/home/{user}`, MySQL dumps, DNS zones, and config | Destination credentials encrypted at rest; storage quota checks | `Backups`, `Servers` | Phase 3 |
| **cPanel Account Backup & Download**<br>Generates full cPanel archive (`cpmove-{user}.tar.gz`) or partial (home dir, single database, email forwarders). | [cPanel Backup](https://docs.cpanel.net/cpanel/files/backup/) | Core | `pkgacct` script, streaming gzip | Customer | `backup_archives` | Executes non-blocking queue job to pack tenant files into archive | Download link secured via signed URL with expiration; resource limits prevent CPU starvation | `Backups` | Phase 3 |
| **Remote Backup Destinations**<br>Transfers backup archives to remote SFTP, Amazon S3, Google Cloud Storage, or WebDAV destinations. | [Backup Additional Destinations](https://docs.cpanel.net/whm/backup/backup-configuration/#additional-destinations) | Core WHM | AWS S3 SDK, Flysystem SFTP/S3, WebDAV | Admin | `backup_destinations` | Streams backup streams to remote object store using multi-part upload | Keys stored in encrypted vault; TLS encrypted transport | `Backups` | Phase 3 |

---

### 3.10 Security, Isolation & Access Control (WHM & cPanel)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **cPHulk Brute Force Protection**<br>Monitors login failures on Webmail, cPanel, WHM, SSH, FTP, Dovecot; bans offensive IPs. | [cPHulk Brute Force](https://docs.cpanel.net/whm/security/cphulk-brute-force-protection/) | Core WHM | SQLite / MySQL log table, iptables / ipset rules | Admin | `security_ip_bans`, `security_login_failures`, `security_whitelists` | Inserts ban into Linux kernel `ipset` or drops connections via `iptables` | Prevents lockout of admin IP; rate limits login attempts across all protocols | `Security` | Phase 3 |
| **ModSecurity Management**<br>Installs, activates, and configures OWASP Core Rule Set (CRS) or custom WAF rule definitions. | [ModSecurity](https://docs.cpanel.net/whm/security/modsecurity-tools/) | Core WHM | Apache `mod_security2` / Nginx `libmodsecurity3` | Admin, Customer | `waf_rules`, `waf_rule_exceptions`, `waf_audit_logs` | Deploys rule files into web server config, audits hits, toggles rules per domain | Syntax validation via `nginx -t` or `apachectl configtest` before reloading | `Security`, `Websites` | Phase 3 |
| **Two-Factor Authentication (2FA)**<br>Enforces RFC 6238 TOTP (Google Authenticator, Authy, 1Password) for admin and customer logins. | [Two-Factor Authentication](https://docs.cpanel.net/cpanel/security/two-factor-authentication-2fa/) | Core | PHP TOTP engine, QR code generation | Admin, Reseller, Customer | `user_two_factor_credentials`, `user_recovery_codes` | Validates time-based 6-digit tokens and one-time recovery codes | Rate limits token attempts; recovery codes hashed in database | `Identity` | Phase 1 |
| **Hotlink & Leech Protection**<br>Prevents external websites from directly linking to images/media; limits excessive downloads. | [Hotlink Protection](https://docs.cpanel.net/cpanel/security/hotlink-protection/) | Core | Apache `.htaccess` rewrite rules | Customer | `website_security_rules` | Writes condition blocks preventing `HTTP_REFERER` mismatch | URL sanitization; preserves authorized referrers | `Websites`, `Security` | Phase 2 |

---

### 3.11 Account & Reseller Administration (WHM)

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Hosting Account Lifecycle**<br>Create, modify, suspend, unsuspend, terminate accounts; change package, passwords, or primary domain. | [Create Account](https://docs.cpanel.net/whm/account-functions/create-a-new-account/) | Core WHM | Linux user management, web/mail/db daemon configuration | Admin, Reseller | `hosting_accounts`, `account_suspensions` | `useradd`, sets quotas in `/etc/fstab` or XFS quotas, generates skeleton directory | Reseller quota check; password entropy verification; safe deletion protection | `Accounts`, `Resellers` | Phase 2 |
| **Hosting Packages (Plans)**<br>Defines resource quotas (disk space, bandwidth, max domains, subdomains, emails, databases, FTP). | [Add a Package](https://docs.cpanel.net/whm/packages/add-a-package/) | Core WHM | Database configuration | Admin, Reseller | `hosting_plans`, `hosting_plan_features` | Enforces allocation limits upon account creation or upgrade | Reseller cannot create plans exceeding their parent reseller pool | `HostingPlans`, `Resellers` | Phase 2 |
| **Reseller Center & Privileges**<br>Designates reseller status, delegates ACL permissions (e.g. can create accounts, edit DNS, restart services). | [Reseller Center](https://docs.cpanel.net/whm/resellers/reseller-center/) | Core WHM | ACL definitions, account ownership assignment | Admin | `resellers`, `reseller_quotas`, `reseller_acls` | Enforces hierarchical ownership tree in all queries | Strict tenant boundary: reseller cannot view or touch sibling reseller resources | `Resellers`, `Identity` | Phase 2 |
| **Account Modification & Downgrade/Upgrade**<br>Dynamically updates account quotas, allocated IP addresses, and enabled features. | [Modify an Account](https://docs.cpanel.net/whm/account-functions/modify-an-account/) | Core WHM | Linux `setquota`, Apache vhost update, DNS update | Admin, Reseller | `hosting_accounts`, `quota_audit_logs` | Adjusts filesystem quota via `edquota`/`xfs_quota`; re-renders vhosts | Entitlement check against reseller allowances; graceful rollback on failure | `Accounts`, `HostingPlans` | Phase 2 |

---

### 3.12 Server Monitoring, Metrics & Service Status

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Service Manager & Health Daemon (chksrvd)**<br>Monitors running state of system daemons (HTTP, MySQL, DNS, Exim, Dovecot, SSH); restarts dead services. | [Service Manager](https://docs.cpanel.net/whm/service-configuration/service-manager/) | Core WHM | `systemd`, `systemctl`, socket polling | Admin | `server_services`, `service_status_history` | Issues `systemctl status/restart/reload` through isolated agent daemon | Restricted to SuperAdmin; audit logged; flap detection prevents infinite crash loops | `ServerServices`, `Monitoring` | Phase 3 |
| **Server Information & Resource Monitoring**<br>Live telemetry of CPU load, memory utilization, swap, disk mount points, and active processes. | [Server Information](https://docs.cpanel.net/whm/server-status/server-information/) | Core WHM | Linux `/proc/stat`, `/proc/meminfo`, `/proc/loadavg`, `df -h` | Admin | `server_telemetry_samples` | Reads Linux virtual proc filesystem and formats real-time metrics | Read-only; sanitized against shell injection; cached to prevent I/O load | `Monitoring`, `Servers` | Phase 3 |
| **Bandwidth & Traffic Analytics**<br>Aggregates HTTP, FTP, IMAP/POP, and SMTP bandwidth per domain and per account over billing periods. | [Bandwidth](https://docs.cpanel.net/cpanel/metrics/bandwidth/) | Core | Log parsing (AWStats, Webalizer) or netfilter accounting | Customer, Admin | `bandwidth_usage_daily` | Aggregates daily transfer bytes from log parser output into database | Enforces bandwidth quota soft/hard limits (triggers account suspension) | `Monitoring`, `Accounts` | Phase 3 |

---

### 3.13 Advanced Tools & Developer Integrations

| Feature Name & Description | Reference URL | Type | OS & Dependencies | Roles | Database Entities | Server-Side Operations | Security & Auth | Module Dependency | VortanCP Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Git Version Control**<br>Clones, pulls, manages branches, and triggers automated deployment scripts upon push or webhook. | [Git Version Control](https://docs.cpanel.net/cpanel/files/git-version-control/) | Core | Git binary (`/usr/bin/git`), SSH deploy keys | Customer | `git_repositories`, `git_deployments` | Executes `git clone`, `git pull`, and runs `.cpanel.yml` build steps under customer UID | Secret masking for deploy keys and webhook payloads; execution within user jail | `GitDeployment` | Phase 3 |
| **WordPress Toolkit (cPanel)**<br>Installs, detects, updates core/plugins/themes, clones, stages, and audits WordPress sites. | [WordPress Toolkit](https://docs.cpanel.net/knowledge-base/cpanel-developed-plugins/cpanel-wordpress-toolkit/) | First-Party Extension | WP-CLI, PHP runtime, MySQL | Customer, Admin | `wp_instances`, `wp_plugins`, `wp_themes`, `wp_backups` | Executes `wp core install`, `wp plugin update`, creates staging clones and database copies | Sandboxed WP-CLI execution; validates database credentials; checks PHP version compatibility | `Wordpress`, `ApplicationManager` | Phase 3 |
| **cPanel Account Migration / Transfer Tool**<br>Pulls accounts from remote cPanel/WHM server via SSH or API token and restores them locally. | [Transfer Tool](https://docs.cpanel.net/whm/transfers/transfer-tool/) | Core WHM | SSH client, SCP/Rsync, `pkgacct`, `restorepkg` | Admin | `migration_jobs`, `migration_tasks` | Connects to remote WHM API, packages account, streams tarball, restores users, DBs, and DNS | Encrypted remote credentials; dry-run preflight checks; IP re-mapping | `Migrations` | Phase 4 |

---

## 4. cPanel / WHM API Ecosystem Summary
cPanel provides two primary API layers:
1. **WHM API 1 (JSON/XML):** Administrative orchestration (e.g. `createacct`, `suspendacct`, `modifyacct`, `servicestatus`). Authenticated via WHM API Tokens (`whm <user>:<token>`).
2. **UAPI (cPanel User API):** End-user operations (e.g. `Email::add_pop`, `Mysql::create_database`, `ZoneEdit::fetch_zone_records`, `SSL::install_ssl`). Authenticated via cPanel API Tokens or active session cookies.

**Key Architecture Takeaway for VortanCP:**
In VortanCP, all Livewire components, API controllers, and CLI commands will consume unified internal Domain Actions and Application Services. The external REST API will map directly to these same services with OAuth2/Personal Access Token scopes, ensuring 100% behavioral identity across UI and API.
