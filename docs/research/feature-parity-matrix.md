# Comprehensive Feature Parity Matrix: cPanel/WHM vs. Plesk Obsidian vs. VortanCP

## 1. Overview & Classification Framework

This document synthesizes the capabilities of **cPanel/WHM** and **Plesk Obsidian** side-by-side and establishes the definitive implementation strategy for **VortanCP**. 

### Dependency Classification Legend:
- **Direct (D):** Implemented directly inside Laravel 13, Eloquent, Livewire, and database logic without external daemon dependencies.
- **Server Agent (SA):** Requires privileged execution via the isolated Vortan Server Provisioning Agent (Linux systemd, filesystem, daemons, package manager).
- **External Provider (EP):** Requires external third-party or cloud APIs (e.g. Let's Encrypt ACME CA, AWS S3, Cloudflare DNS, Stripe).
- **Optional / Commercial (OC):** Specialized extensions or licensed engines (e.g. ClamAV, ModSecurity commercial rulesets, WP Toolkit Smart Updates).

---

## 2. Feature Parity Matrix

### 2.1 Identity, Multitenancy & Access Control

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **User Hierarchy & Tenancy** | Root Admin, Reseller, cPanel User | Administrator, Reseller, Customer, Additional User | `Identity`, `Accounts`, `Resellers` | D | Adopt Plesk's flexible model: User -> Customer Org -> Multiple Subscriptions / Hosting Accounts. Resellers own pools of resources. | Phase 1 & 2 |
| **Authentication & 2FA** | cPanel 2FA (TOTP), WHM 2FA, Session security | Two-Factor Authentication extension (Google Auth), Session Idle Timeout | `Identity` | D | RFC 6238 TOTP with encrypted secrets, recovery codes, trusted devices, login rate limiting, session revocation. | Phase 1 |
| **API Tokens & Scopes** | WHM API Tokens, cPanel API Tokens | Plesk REST API Keys with IP restrictions | `Api`, `Identity` | D | Laravel Sanctum with scoped permissions (`domains:read`, `databases:write`), expiration, IP restrictions. | Phase 1 & 4 |
| **Delegated Access / Team Members** | User Manager (subaccounts: email, ftp, webdisk) | Additional User Accounts with custom User Roles | `Identity`, `Accounts` | D | Fine-grained RBAC with Laravel Policies: customer can invite team members with restricted capabilities. | Phase 1 & 2 |
| **Audit Trails & Security Logs** | WHM Audit Log, `cpanellogd` | Plesk Action Log | `Audit`, `Identity` | D | Immutable database audit table capturing user, IP, action, resource, timestamp, and diff payload. | Phase 1 |

---

### 2.2 Server Inventory & Infrastructure Management

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Multi-Server Management** | WHM DNS Cluster, cPanel Configuration Clusters | Plesk Multi-Server extension (discontinued), central WebHost | `Servers`, `Networking` | SA, D | Multi-node architecture from day one: Master panel connects to multiple Linux worker nodes via gRPC / Mutual TLS Agent. | Phase 2 |
| **Service Status & Control** | WHM Service Manager (`chksrvd`), restart daemons | Service Management (`systemctl` via panel) | `ServerServices` | SA | Allowlisted systemd controller for Nginx, Apache, PHP-FPM, MariaDB, Postfix, Dovecot, BIND, Redis. | Phase 3 |
| **Health & Telemetry** | Server Information (`/proc`), `top` / `sar` reports | Plesk Health Monitor (graphs for CPU, RAM, disk I/O) | `Monitoring`, `Servers` | SA, D | Telemetry collector agent reporting CPU, memory, load, disk I/O, swap, and queue lag; visualized with Tailwind charts. | Phase 3 |
| **IP Address Allocation** | WHM IP Functions (Add IP, Assign IP, NAT) | IP Addresses (Shared vs. Dedicated pools) | `Networking`, `Servers` | SA, D | IPv4 and IPv6 address inventory, shared vs. dedicated account assignment, automated reverse DNS workflows. | Phase 3 |

---

### 2.3 Hosting Plans, Subscriptions & Quota Enforcement

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Hosting Packages / Plans** | WHM Packages (Disk, BW, Email, DB limits) | Service Plans & Add-on Plans | `HostingPlans` | D | Comprehensive plan templates with customizable quotas for all 28 modules, overselling toggles, plan inheritance. | Phase 2 |
| **Subscriptions & Sandboxes** | cPanel Accounts (1:1 with Linux user) | Subscriptions (assigned to Customer, isolated system user) | `Accounts`, `HostingPlans` | SA, D | Independent hosting subscriptions containing docroots, PHP pools, database prefixes, and mail domains. | Phase 2 |
| **Quota Enforcement Engine** | OS Quota (`xfs_quota` / `quotactl`), Bandwidth counters | Filesystem Quotas, Trajectory alerts | `HostingPlans`, `Monitoring` | SA, D | Centralized `EntitlementService` checks limits before all mutations; background reconciliation against OS storage. | Phase 2 |
| **Reseller Resource Pools** | Reseller Center (allocation of disk/bw pool) | Reseller Resource Limits & Permissions | `Resellers`, `HostingPlans` | D | Resellers receive fixed allocation caps; system validates that sum of child plans does not exceed reseller allowance. | Phase 2 |

---

### 2.4 Websites, Domains & Web Servers

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **VHost Architecture** | Apache 2.4 + Nginx Reverse Proxy (EasyApache 4) | Nginx Reverse Proxy + Apache OR Nginx Standalone | `Websites` | SA | Dual-mode adapter: supports Nginx-only (pure performance) or Nginx + Apache backend (full `.htaccess` support). | Phase 2 |
| **Domain Types** | Primary, Addon, Subdomain, Alias (Parked) | Primary Domain, Subdomain, Domain Alias | `Domains`, `Websites` | SA, D | Unified `Domain` model with polymorphic types (`primary`, `addon`, `subdomain`, `alias`), distinct docroots. | Phase 2 |
| **URL Redirects & Error Pages** | Redirects manager (`.htaccess`), Error Pages | Hosting Settings (301 redirect, custom error pages) | `Websites` | SA, D | Template-based vhost snippet generation and `.htaccess` management with path validation. | Phase 2 |
| **HTTP/2 & HTTP/3 (QUIC)** | EasyApache 4 HTTP/2 toggle | Nginx HTTP/2 and HTTP/3 support | `Websites`, `ServerServices` | SA | Configured at web server adapter level with modern cipher suites and protocol negotiation. | Phase 2 |

---

### 2.5 DNS Management

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Zone Management & Records** | cPanel Zone Editor (A, AAAA, CNAME, MX, TXT, SRV, CAA) | Plesk DNS Zone Editor (RFC compliant) | `Dns` | SA, D | Robust zone parser and builder; publishes to PowerDNS (API/DB) or BIND 9 zone files with serial auto-increment. | Phase 2 |
| **DNSSEC** | cPanel DNSSEC (automated KSK/ZSK generation) | Plesk DNSSEC extension | `Dns` | SA | Automated key generation, scheduled rollover, DS record generation for registrar delegation. | Phase 2 |
| **Clustering & Secondary DNS** | WHM DNS Cluster (DNSOnly nodes) | Slave DNS Manager extension | `Dns`, `Servers` | SA, D | Master-to-slave replication via PowerDNS native replication or TSIG-authenticated AXFR/IXFR transfers. | Phase 2 |

---

### 2.6 SSL/TLS Certificate Management

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Automated Certificates (ACME)** | AutoSSL (Let's Encrypt / Sectigo cPanel CA) | SSL It! (Let's Encrypt, DigiCert, Sectigo) | `Ssl` | SA, EP | Native ACME client supporting HTTP-01 webroot challenge and DNS-01 challenge for wildcard certs. | Phase 2 |
| **Custom / Commercial SSL** | SSL/TLS Manager (Paste CRT, KEY, CA Bundle) | SSL/TLS Certificates (Upload or Paste) | `Ssl` | SA, D | X.509 OpenSSL modulus validation, intermediate chain completion, secure root-only storage. | Phase 2 |
| **HTTPS Enforcement & HSTS** | Force HTTPS Redirect toggle | Permanent SEO-safe 301 redirect from HTTP to HTTPS | `Websites`, `Ssl` | SA, D | Automated web server vhost rewrite with optional HSTS (`Strict-Transport-Security`) headers. | Phase 2 |

---

### 2.7 Files & Data Transfer

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Web File Manager** | cPanel File Manager (Zip, Unzip, Code Editor, Chmod) | Plesk File Manager (Editor, Chmod, Search) | `FileManager` | SA, D | High-performance Livewire component with chunked uploads, in-browser editor, zip/tar extraction, strict path jail. | Phase 2 |
| **FTP / FTPS** | Pure-FTPd / ProFTPD virtual users | ProFTPD virtual user accounts | `FileTransfer` | SA | Virtual user management mapped to subscription docroots; TLS 1.3 enforced; directory confinement. | Phase 2 |
| **SSH & Public Keys** | SSH Access (`jailshell` or normal shell) | SSH Terminal Access (chrooted shell or `/bin/bash`) | `FileTransfer`, `Security` | SA | User key management, systemd sandboxed chroot jail (`bubblewrap` or `systemd-nspawn`), disabled by default. | Phase 2 |
| **WebDAV / Web Disk** | Web Disk (Digest auth over port 2078) | WebDAV sharing | `FileTransfer` | SA | Apache/Nginx WebDAV endpoints with TLS enforcement and user-specific digest authentication. | Phase 2 |

---

### 2.8 Database Management

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Engines Supported** | MySQL 8.0, MariaDB 10.6+ | MariaDB/MySQL, PostgreSQL, MSSQL (Windows) | `Databases` | SA, D | First-class MariaDB/MySQL and PostgreSQL adapters via standard database connectors. | Phase 2 |
| **Prefix Isolation & Grants** | Mandatory `{user}_{db}` prefix, user privilege matrix | Optional prefix, per-database users | `Databases` | SA, D | Configurable prefix policy (`{username}_{dbname}`); granular GRANT privileges (`SELECT`, `INSERT`, `ALTER`, etc.). | Phase 2 |
| **Web GUI Administration** | phpMyAdmin SSO | phpMyAdmin SSO, phpPgAdmin SSO | `Databases` | D | One-time temporary HMAC-signed SSO token redirects to standalone phpMyAdmin / pgAdmin instances. | Phase 2 |
| **Dumps & Restoration** | cPanel Database Backup (`mysqldump`) | Database Export / Import (`mysqldump`, `pg_dump`) | `Databases`, `Backups` | SA | Asynchronous streaming database dump and import with strict execution timeouts and quota validation. | Phase 2 |

---

### 2.9 Email System

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Mail Transport & Storage** | Exim MTA + Dovecot IMAP/POP3 (Maildir) | Postfix MTA + Dovecot IMAP/POP3 | `Email` | SA | Postfix + Dovecot stack; virtual mailbox database; quota enforcement via Dovecot Dict / LMTP. | Phase 2 |
| **Deliverability (SPF, DKIM, DMARC)** | Email Deliverability tool (DKIM key generator) | DKIM, SPF, and DMARC Protection | `Email`, `Dns` | SA, D | Automated 2048-bit DKIM keypair generation, Rspamd / OpenDKIM integration, auto-publishing DNS TXT records. | Phase 2 |
| **Spam & Virus Defense** | SpamAssassin, Greylisting | SpamAssassin, Amavis, Plesk Premium Antivirus | `Email`, `Security` | SA, OC | Rspamd (modern, fast, low memory) or SpamAssassin; optional ClamAV antivirus milter. | Phase 2 & 3 |
| **Outbound Rate Limiting** | Outgoing Mail Limits per hour | Limitations on Outgoing Mail | `Email`, `Security` | SA | Redis-backed token bucket rate limiter tracking outbound messages per mailbox and domain; drops spam spikes. | Phase 2 |
| **Webmail Access** | Roundcube SSO | Roundcube SSO, Horde | `Email` | D | Integrated Roundcube webmail with automatic SSO login, autodiscover/autoconfig mobile profiles. | Phase 2 |

---

### 2.10 PHP & Application Runtimes

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Multi-PHP Selection** | MultiPHP Manager (ea-php74 to ea-php84) | Multiple PHP versions via PHP-FPM / FastCGI | `PhpManager`, `Websites` | SA | Independent PHP-FPM socket pools per domain/account, supporting PHP 7.4 through 8.4 simultaneously. | Phase 2 |
| **Directive Customization** | MultiPHP INI Editor (`memory_limit`, etc.) | PHP Settings (`.user.ini` / pool directives) | `PhpManager` | SA, D | Customer-editable `.user.ini` with plan-defined caps on `max_execution_time` and `memory_limit`. | Phase 2 |
| **Node.js, Python, Ruby** | cPanel Application Manager (Passenger) | Node.js / Python / Ruby extensions (Passenger) | `ApplicationManager` | SA | Optional process manager adapter (PM2 or Phusion Passenger) running user-jailed processes behind Nginx reverse proxy. | Phase 3 |

---

### 2.11 Application Management & WordPress Toolkit

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **1-Click WordPress Setup** | WordPress Toolkit | WordPress Toolkit (Plesk flagship) | `Wordpress` | SA, D | Automated provisioning: download WP core, create DB/user, generate `wp-config.php`, configure admin via WP-CLI. | Phase 3 |
| **Cloning & Staging** | WordPress Toolkit Clone/Stage | Staging environment with 1-click push to live | `Wordpress`, `Backups` | SA, D | Copies files, clones database, executes `wp search-replace` to update URLs; creates safety preflight backup. | Phase 3 |
| **Plugin & Theme Management** | WP Toolkit Plugin Manager | WP Toolkit Plugin & Theme auto-updates | `Wordpress` | SA, D | Livewire UI displaying installed items, update status, toggle active/inactive, bulk updates via queue. | Phase 3 |

---

### 2.12 Backup, Disaster Recovery & Migration

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Backup Granularity** | Full server, user cpmove, partial (DB/mail) | Server, Reseller, Subscription, granular items | `Backups` | SA, D | Granular backups: Full account, website-only, database-only, email-only; streaming non-blocking jobs. | Phase 3 |
| **Remote Destinations** | SFTP, Amazon S3, Google Drive, WebDAV | Amazon S3, Google Drive, OneDrive, SFTP | `Backups` | SA, EP | Flysystem adapters supporting S3-compatible storage (AWS, Wasabi, MinIO, Backblaze B2) and remote SFTP. | Phase 3 |
| **Selective Restore** | WHM Backup Restoration, cPanel partial restore | Selective restoration wizard | `Backups` | SA, D | Interactive restore preview showing files, databases, mailboxes in archive; safe preflight collision checks. | Phase 3 |
| **Account Migration / Import** | WHM Transfer Tool (cPanel to cPanel) | Plesk Migrator (cPanel, Plesk, Confixx import) | `Migrations` | SA, D | Automated migration engine: parses `cpmove-*.tar.gz` and Plesk XML backups; restores DBs, DNS, mail, and web files. | Phase 4 |

---

### 2.13 Security & Firewall Management

| Functional Category | cPanel / WHM Equivalent | Plesk Obsidian Equivalent | VortanCP Module | Dependency Class | Parity & Implementation Strategy | Target Phase |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Brute Force Defense** | cPHulk Brute Force Protection | Fail2ban Integration | `Security` | SA | Fail2ban / ipset integration monitoring login endpoints across panel, SSH, FTP, Dovecot, Postfix. | Phase 3 |
| **Web Application Firewall** | ModSecurity Tools (OWASP CRS) | ModSecurity (OWASP, Atomic rulesets) | `Security`, `Websites` | SA | Nginx/Apache ModSecurity engine; toggle WAF per domain; rule exclusion manager; audit log viewer. | Phase 3 |
| **Malware & Antivirus Scan** | ImunifyAV / ClamAV integration | Plesk Premium Antivirus / ImunifyAV | `Security` | SA, OC | ClamAV / Maldet scanner integration with scheduled scans and file quarantine actions. | Phase 3 |

---

## 3. Summary of Architectural Distinctions & Design Choices

1. **Decoupled Control Plane vs. Monolithic Root Runner:**
   - Both cPanel and Plesk run their control planes on the same server, often with elevated permissions (`cpsrvd` setuid or `sw-engine` root wrappers).
   - **VortanCP Decision:** Strictly decouple the Laravel control plane (which runs under an unprivileged `vortan` user) from privileged node execution. All operating system mutations execute via a dedicated **Vortan Node Agent** over TLS-authenticated IPC/gRPC.
2. **Account Model:**
   - cPanel couples 1 Linux User = 1 cPanel Account.
   - Plesk separates Customer from Subscriptions.
   - **VortanCP Decision:** Adopt the Customer -> Subscriptions model. A single customer identity can manage multiple isolated hosting accounts across one or many servers.
3. **Database Architecture:**
   - cPanel relies on a mix of text files (`/var/cpanel/users/`), SQLite databases, and MySQL.
   - Plesk uses a centralized local MySQL database (`psa`).
   - **VortanCP Decision:** Use a high-performance relational database (PostgreSQL or MySQL 8.0/MariaDB) with clean Eloquent models, foreign keys, schema migrations, and full transactional consistency.
