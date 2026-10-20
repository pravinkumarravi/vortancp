# VortanCP - Next-Gen Modular Web Hosting Control Panel

<p align="center">
  <strong>An enterprise-grade, high-performance, modular alternative to cPanel/WHM and Plesk.</strong><br>
  Built with Laravel 13, Livewire v4, Tailwind CSS, Alpine.js, and an isolated privileged Linux daemon.
</p>

---

## 🚀 Key Highlights & Architectural Strengths

- **Truly Modular Architecture**: Every capability is an independent module in `app/Modules/{Module}/` with its own business logic, Livewire components, Blade views, migrations, and test coverage.
- **Three Core User Portals**:
  1. **Administrator Panel (`/admin`)**: Fleet node inventory, global accounts, package quotas, cluster firewall, ModSecurity WAF, and audit logs.
  2. **Reseller Panel (`/reseller`)**: Sub-reseller tenancy, customer account provisioning, custom package definitions, and pooled quota management.
  3. **Customer Hosting Panel (`/app`)**: Self-service domain management, DNS zone editor, 1-click Let's Encrypt SSL, safe File Manager, MariaDB databases, Postfix/Dovecot email, isolated PHP versions, Pure-FTPd virtual users, cron jobs, backups, Git webhooks, WordPress Toolkit, and 1-Click App Installer.
- **Root-Isolated Daemon (`vortan-agent`)**:
  - The web application executes as unprivileged user `vortan`.
  - Privileged Linux host operations (Nginx, PHP-FPM, BIND9, Postfix, UFW, system users, quotas) are orchestrated strictly via **allowlisted JSON-RPC 2.0 over Unix Domain Socket / mTLS** with cryptographic HMAC-SHA256 authentication.
- **100% Green Test Suite**: 182 passing tests with 691 assertions covering unit compiler logic and end-to-end Livewire interactivity.

---

## 📦 Modular Inventory

| Module | Purpose & Core Capabilities |
| :--- | :--- |
| **Core** | Dynamic `ModuleRegistry`, `NavigationRegistry`, priority sort, dependency resolution. |
| **Identity** | RBAC (`super_admin`, `admin`, `reseller`, `customer`), permission gates, TOTP 2FA. |
| **Audit** | Immutable, cryptographically signed audit logger with automated sensitive parameter redaction. |
| **Dashboard** | Real-time statistics, quota consumption meters, server health, and quick actions. |
| **Servers** | Multi-node cluster inventory, health ping telemetry, and optimal placement engine. |
| **Accounts & Resellers** | Multi-tenant customer accounts, sub-reseller pools, lifecycle suspension/activation. |
| **HostingPlans** | Packages, quota caps (disk, bandwidth, domains, databases), `EntitlementService` assertions. |
| **Domains** | Primary/addon/subdomains, document root isolation, Nginx virtual host compiler, redirects. |
| **Dns** | RFC 1035 compliant BIND zone compiler, automated SOA serial increments, standard hosting records. |
| **Ssl & AutoSSL** | OpenSSL keypairs, CSR generator, Let's Encrypt ACME 1-click issuance, and automated cluster-wide **AutoSSL Fleet Engine** with proactive 30-day renewal scanner and automated Nginx certificate binding. |
| **FileManager** | In-jail filesystem navigator, path traversal protection, code editor, file upload/download. |
| **Databases** | MariaDB/MySQL database & user provisioning, quota caps, privilege assignments. |
| **Email** | Postfix/Dovecot mailboxes, quotas, forwarders, automated 2048-bit DKIM keypair generation. |
| **PhpManager** | Multi-PHP version manager (8.1, 8.2, 8.3, 8.4), isolated FPM pool compiler, directive overrides. |
| **Ftp** | Virtual user Pure-FTPd/ProFTPd format compiler, homedir chroots, password hashing. |
| **Cron** | Scheduled task manager, standard Unix crontab compiler, command injection sanitization. |
| **Backups** | Full and selective account archives (`manifest.json`), SHA-256 integrity verification, automated pruning. |
| **Firewall** | UFW & IPTables rule compiler, port ranges, CIDR validation, Fail2ban ban/unban shield. |
| **SecurityCenter** | ModSecurity v3 WAF compiler, OWASP Core Rule Set, ClamAV-compatible scanner, quarantine vault. |
| **WordPressToolkit** | 1-click installer, automated DB provisioning, auto-updates, XML-RPC & file edit hardening, SSO login. |
| **Git** | SSH deploy key generation, remote repo tracking, deployment branch checkout, push webhooks. |
| **AppInstaller** | 1-click installer for Laravel, phpMyAdmin, Roundcube Webmail, Nextcloud Hub, Drupal, and Joomla. |
| **Metrics** | Live CPU, RAM, disk, load averages, account quota utilization gauges, and warning threshold alerts. |
| **Agent** | Privileged host orchestration daemon, allowlisted JSON-RPC 2.0 protocol, HMAC verification. |
| **Billing & WHMCS** | RESTful external billing API integration for WHMCS, Blesta, and HostBill with API key management. |
| **Service Manager** | Core systemd daemon monitoring, 1-click service control (restart/reload/stop), and crash recovery watchdog. |
| **Log Inspector** | Real-time server log inspection (Nginx, PHP-FPM, MariaDB, Mail, Agent, Fail2ban) & customer domain logs. |

---

## 🛠️ Technology Stack

- **Backend Framework**: Laravel 13.37.0 (PHP 8.3 / 8.4)
- **Frontend / Reactivity**: Livewire v4.4.7, Alpine.js, Blade
- **Styling**: Tailwind CSS v4 (`@tailwindcss/vite`) + Custom Dark Mode TailAdmin theme
- **Database**: SQLite (Local Dev / Testing) / MariaDB 10.11+ / MySQL 8.0+ (Production)
- **Web Server**: Nginx with PHP-FPM
- **Name Server**: BIND9
- **Mail Stack**: Postfix + Dovecot + OpenDKIM
- **FTP Daemon**: Pure-FTPd (Virtual Users)
- **Security & WAF**: UFW, Fail2ban, ModSecurity v3, OWASP Core Rule Set

---

## 🚀 Quick Start (Development)

### 1. Requirements
- PHP 8.3 or 8.4 with extensions: `pdo_sqlite`, `curl`, `mbstring`, `openssl`, `zip`
- Composer 2+
- Node.js 20+ and npm

### 2. Setup
```bash
# Clone and enter directory
git clone https://github.com/pravinkumarravi/vortancp.git
cd vortancp

# Install PHP and Node dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run database migrations and seeders
php artisan migrate --force
php artisan db:seed --class=IdentityDatabaseSeeder --force

# Compile assets
npm run build
```

### 3. Run Development Server
```bash
php artisan serve
```
Access the application at `http://127.0.0.1:8000`.

### 4. Default Seeded Credentials
- **Super Administrator**: `admin@vortan.local` / `password`
- **Reseller**: `reseller@vortan.local` / `password`
- **Customer**: `customer@vortan.local` / `password`

---

## 🧪 Running Tests

The test suite validates both Unit compilation logic and Livewire Feature interactivity:

```bash
# Run all 182 tests (691 assertions)
php artisan test

# Run a specific domain module's tests
php artisan test --filter=LogViewer
php artisan test --filter=ServiceManager
php artisan test --filter=BillingApi
php artisan test --filter=Agent
php artisan test --filter=WordPress
php artisan test --filter=Databases
```

---

## 💳 WHMCS & Billing Integration API

VortanCP provides a native REST API for WHMCS, Blesta, and HostBill automation:

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/billing/accounts/create` | Provisions account, primary domain, vhost, DNS zone & mailbox |
| `POST` | `/api/v1/billing/accounts/{user}/suspend` | Suspends account with reason |
| `POST` | `/api/v1/billing/accounts/{user}/unsuspend` | Restores account to active status |
| `POST` | `/api/v1/billing/accounts/{user}/terminate` | Permanently deletes account and associated assets |
| `POST` | `/api/v1/billing/accounts/{user}/change-package` | Upgrades/downgrades hosting plan quotas |
| `GET` | `/api/v1/billing/accounts/{user}/status` | Fetches live disk/bandwidth usage and status |
| `GET` | `/api/v1/billing/accounts` | Paginated listing of accounts |
| `GET` | `/api/v1/billing/plans` | Lists available plans and resource allocations |

Authenticate using the `X-API-Key: {token}` header or `Authorization: Bearer {token}`. API keys can be generated and managed directly in the Admin Panel under **System Settings → API & Billing**.

---

## 🌐 Production Installation (Ubuntu 22.04 / 24.04 & Debian 12)

Run the automated idempotent installer as root on a fresh server:

```bash
curl -sSL https://raw.githubusercontent.com/pravinkumarravi/vortancp/main/scripts/vortan-installer.sh | bash
```

The script automatically sets up:
- Complete web, DNS, database, mail, and FTP stacks.
- Unprivileged system user `vortan`.
- Systemd units `vortan-agent.service` and `vortan-queue.service`.
- UFW firewall rules and Fail2ban filters.
- MariaDB production database and credentials.

---

## 📄 License

VortanCP is open-sourced software licensed under the [MIT License](LICENSE).

