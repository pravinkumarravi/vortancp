# VortanCP Phased Implementation Roadmap & Milestone Tracker

## 1. Roadmap Overview & Execution Methodology

The development of **VortanCP** is organized into six consecutive, strictly governed phases. Development strictly adheres to a test-driven, buildable-state discipline: no code is merged or phase marked complete without passing automated unit, feature, authorization, and tenant-isolation tests.

---

## 2. Phased Development Breakdown

```
+---------------------------------------------------------------------------------------+
|  PHASE 0: RESEARCH & ARCHITECTURE BASELINE (COMPLETED)                                |
|  Official cPanel & Plesk documentation deep-dive, feature inventory, parity matrix,   |
|  system architecture, agent isolation design, database ownership, Livewire taxonomy.   |
+-------------------------------------------+-------------------------------------------+
                                            |
                                            v
+---------------------------------------------------------------------------------------+
|  PHASE 1: APPLICATION FOUNDATION & CORE RUNTIME                                       |
|  Laravel 13 bootstrap, TailAdmin component library, Identity/Auth (RBAC + 2FA),       |
|  Module Registration Engine, dynamic navigation builder, Audit & Notification system.|
+-------------------------------------------+-------------------------------------------+
                                            |
                                            v
+---------------------------------------------------------------------------------------+
|  PHASE 2: CORE HOSTING INFRASTRUCTURE WORKFLOWS                                      |
|  Servers & Agent connectivity, Customers & Accounts, Reseller Pools, Hosting Plans    |
|  & Quotas, Domains & VHosts (Nginx/Apache), DNS Zones, SSL/TLS (ACME), File Manager,  |
|  Databases (MariaDB/MySQL/PostgreSQL), Email (Postfix/Dovecot), PHP Multi-Version.     |
+-------------------------------------------+-------------------------------------------+
                                            |
                                            v
+---------------------------------------------------------------------------------------+
|  PHASE 3: OPERATIONAL SERVICES, AUTOMATION & SECURITY                                 |
|  Asynchronous Server Provisioning, Backup & Disaster Recovery (S3/SFTP), Cron Jobs,   |
|  Git Deployments, WordPress Toolkit, Monitoring & Telemetry, Security & Fail2ban,    |
|  System Services Control, IP Address & Subnet Management.                             |
+-------------------------------------------+-------------------------------------------+
                                            |
                                            v
+---------------------------------------------------------------------------------------+
|  PHASE 4: PLATFORM EXPANSION, EXTENSIONS & INTEGRATIONS                               |
|  Account Migrations (cPanel/Plesk import), Extension/Plugin Sandbox, REST API v1      |
|  (Sanctum), Webhooks Dispatcher, Customer Support Tickets, Optional Billing (Stripe).  |
+-------------------------------------------+-------------------------------------------+
                                            |
                                            v
+---------------------------------------------------------------------------------------+
|  PHASE 5: FEATURE PARITY, SECURITY AUDIT & PRODUCTION HARDENING                       |
|  Feature Parity validation, penetration testing / vulnerability assessment,           |
|  performance benchmarking, high-concurrency stress testing, end-to-end documentation.  |
+---------------------------------------------------------------------------------------+
```

---

## 3. Detailed Phase Deliverables & Exit Criteria

### Phase 0: Research & Architecture Baseline (Status: Complete)
- [x] Audit development environment (PHP 8.4.7, Composer 2.8.9, Node v24.13.1, Git).
- [x] Research official cPanel/WHM documentation and compile feature inventory (`docs/research/cpanel-feature-inventory.md`).
- [x] Research official Plesk Obsidian documentation and compile feature inventory (`docs/research/plesk-feature-inventory.md`).
- [x] Create comprehensive Feature Parity Matrix (`docs/research/feature-parity-matrix.md`).
- [x] Design 28-Module Catalog with dependency graph (`docs/architecture/module-catalog.md`).
- [x] Formulate high-level System Architecture (`docs/architecture/system-architecture.md`).
- [x] Author Server Provisioning Agent isolation specification (`docs/architecture/server-agent-design.md`).
- [x] Author Security & Multitenancy Model (`docs/architecture/security-model.md`).
- [x] Author Database Schema & Module Ownership Plan (`docs/architecture/database-ownership.md`).
- [x] Author Livewire, Alpine & TailAdmin Design Patterns (`docs/architecture/livewire-patterns.md`).
- [x] Author Phased Implementation Roadmap (`docs/architecture/implementation-roadmap.md`).

---

### Phase 1: Application Foundation & Core Runtime
- **Target Deliverables:**
  1. Fresh, optimized Laravel 13 application initialized in workspace.
  2. Integration of TailAdmin layout foundation (Sidebar, Header, Breadcrumbs, Notifications dropdown, User profile, Dark/Light mode toggle).
  3. `Module 01: Core Platform` (`Vortan\Core`):
     - Dynamic `ModuleRegistry` with automatic discovery, dependency resolution, migration registration, and lifecycle events.
     - Dynamic role-filtered Navigation Engine (`AdminNavigation`, `ResellerNavigation`, `CustomerNavigation`).
  4. `Module 02: Identity, Authentication & Authorization` (`Vortan\Identity`):
     - User authentication, password reset, Argon2id hashing.
     - RFC 6238 TOTP Two-Factor Authentication with encrypted keys & recovery codes.
     - Role-Based Access Control (RBAC): SuperAdmin, ServerAdmin, Reseller, Customer, TeamMember, ReadOnly.
     - Laravel Policies and Eloquent tenancy scopes.
  5. `Module 27: Audit Logging & Notifications` (`Vortan\Audit`, `Vortan\Notifications`):
     - Immutable `AuditLog` recorder capturing actor, action, target entity, timestamp, IP, and attribute diffs.
     - Database and broadcast notification pipeline.
  6. Shared TailAdmin Blade & Livewire UI components:
     - Form inputs, buttons with loading states, data tables, confirmation modals, badges, alerts, toasts.
- **Exit Criteria:**
  - `php artisan test` passes 100% of Foundation, Authentication, RBAC, Tenancy, and Module Registration test suites.
  - User can log in as Admin, Reseller, or Customer and see their distinct, role-tailored navigation bars.

---

### Phase 2: Core Hosting Infrastructure Workflows
- **Target Deliverables:**
  - `Module 06: Servers`: Server node registration, connection secrets, status heartbeat.
  - `Module 03: Accounts`: Customer organizations & hosting accounts (sandboxes).
  - `Module 04: Resellers`: Reseller creation, resource allocation caps, ownership hierarchy.
  - `Module 05: HostingPlans`: Plan templates, subscription management, `EntitlementService` quota gatekeeper.
  - `Module 07: Websites` & `Domains`: Primary, addon, subdomains, aliases, Nginx/Apache vhost generation, redirects.
  - `Module 08: Dns`: Authoritative DNS zones, standard resource records, serial auto-bumping.
  - `Module 09: Ssl`: Automated ACME / Let's Encrypt certificates, HTTP-01/DNS-01 challenges, custom SSL upload.
  - `Module 10: FileManager`: Livewire directory browser, file upload/download, in-browser editor, zip extraction, chroot jail.
  - `Module 11: FileTransfer`: Virtual FTP accounts, SSH authorized keys, WebDAV.
  - `Module 12: Databases`: MariaDB/MySQL/PostgreSQL databases, users, grants, phpMyAdmin SSO token.
  - `Module 13: Email`: Mail domains, IMAP/POP3 mailboxes, aliases, forwarders, autoresponders, DKIM key generation, Roundcube SSO.
  - `Module 14: PhpManager`: Multi-PHP version selector (7.4 - 8.4), PHP-FPM pool generation, `.user.ini` directives.
- **Exit Criteria:**
  - Automated feature tests verify end-to-end domain creation, quota limits rejection, database provisioning, and mailbox provisioning using mocked infrastructure adapters.

---

### Phase 3: Operational Services, Automation & Security
- **Target Deliverables:**
  - Asynchronous Provisioning Queue & two-phase state machine (`Pending` -> `Running` -> `Active` / `Failed`).
  - `Module 18: Backups`: Full account, website, DB, and mail backup archives; S3/SFTP storage; selective restore wizard.
  - `Module 17: CronJobs`: User crontab management, expression validation, jailed execution.
  - `Module 16: GitDeployment`: Git repo clone, SSH deploy keys, webhook triggers, post-deploy commands.
  - `Module 15: Wordpress`: 1-click WP setup, instance detector, staging environment with 1-click push to live.
  - `Module 19: Monitoring`: CPU, RAM, disk I/O, bandwidth tracking, log streaming.
  - `Module 20: Security`: Fail2ban IP banning, ModSecurity WAF rulesets, ClamAV scanner.
  - `Module 21: ServerServices`: Allowlisted systemd daemon manager (reload, restart, dry-run test).
  - `Module 22: Networking`: IPv4/IPv6 address inventory, reverse DNS workflows.
  - `Module 23: SystemAdmin`: OS security update notifications, disk usage inspection.
- **Exit Criteria:**
  - Background workers execute simulated and mocked real-node operations with full error recovery, retry policies, and rollback on failure.

---

### Phase 4: Platform Expansion, Extensions & Integrations
- **Target Deliverables:**
  - `Module 24: Migrations`: cPanel `cpmove` tarball parser, Plesk XML backup importer, preflight checks, dry-run mode.
  - `Module 25: Extensions`: Plugin discovery, extension hook lifecycle, dynamic navigation and route registration.
  - `Module 26: Api & Webhooks`: Versioned REST API (v1), Sanctum scoped tokens, OpenAPI 3.0 docs, HMAC signed webhooks.
  - `Module 28: Support & Billing`: Customer tickets, internal notes; optional Stripe subscription billing.
- **Exit Criteria:**
  - Ability to import a sample cPanel backup tarball and extract its domains, databases, and mailboxes into VortanCP.

---

### Phase 5: Feature Parity Hardening & Final Deliverables
- **Target Deliverables:**
  - Comparison against cPanel and Plesk feature inventories.
  - Security review (CSRF, XSS, SQLi, SSRF, IDOR, path traversal regression suite).
  - High-concurrency performance benchmarks.
  - Production deployment documentation, systemd service units, and node agent installation scripts.
- **Exit Criteria:**
  - 100% green test suite across all 28 modules.

---

## 4. Immediate Next Step: Initiating Phase 1

With Phase 0 complete and verified, the next step is **Phase 1: Application Foundation & Core Runtime**:
1. Initialize the Laravel 13 framework with PHP 8.4 support.
2. Install and configure Livewire v4, Tailwind CSS, Alpine.js, and Pest/PHPUnit.
3. Establish the TailAdmin dashboard layout and reusable component design system.
4. Build the dynamic `ModuleRegistry` in `Vortan\Core`.
5. Implement `Vortan\Identity` (Authentication, TOTP 2FA, RBAC, and Tenancy).
6. Implement `Vortan\Audit` and `Vortan\Notifications`.
7. Write and execute comprehensive automated test suites to validate Phase 1.
