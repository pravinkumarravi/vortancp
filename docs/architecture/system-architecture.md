# VortanCP System Architecture Proposal

## 1. Architectural Philosophy & Guiding Principles

VortanCP is designed from first principles as an enterprise-grade, secure, multi-tenant hosting management control plane. It resolves the classic architectural pitfalls of legacy control panels:
1. **Decoupled Control Plane & Execution:** The web application *never* runs as root and *never* executes arbitrary shell scripts directly from HTTP worker processes.
2. **Modular Monolith First:** High cohesion and loose coupling within a single Laravel 13 codebase, preventing premature distributed systems overhead while enforcing strict module boundaries.
3. **Plesk-Style Multitenancy with cPanel Simplicity:** Clean entity segregation: `User` -> `Customer Org` -> `Subscriptions` (isolated Linux UID/GID sandboxes).
4. **Reactive Single-Page Feel without Single-Page Complexity:** Laravel 13, Livewire, Alpine.js, Blade, and TailAdmin deliver instantaneous, stateful UI updates without maintaining duplicate client/server models.

---

## 2. High-Level Architecture Diagram

```
+---------------------------------------------------------------------------------------+
|                                    CLIENT BROWSER                                     |
|  TailAdmin UI (Tailwind CSS) | Livewire Components | Alpine.js Micro-Interactions     |
+-------------------------------------------+-------------------------------------------+
                                            | HTTPS / WSS
                                            v
+---------------------------------------------------------------------------------------+
|                                 VORTAN CONTROL PLANE                                  |
|                               (Unprivileged User: vortan)                             |
|                                                                                       |
|  +---------------------------------------------------------------------------------+  |
|  |                              HTTP / API GATEWAY                                 |  |
|  |  Role Middleware | Session Auth | Sanctum API Tokens | Rate Limiting | CSRF    |  |
|  +----------------------------------------+----------------------------------------+  |
|                                           |                                           |
|  +----------------------------------------v----------------------------------------+  |
|  |                         28 INDEPENDENT FEATURE MODULES                          |  |
|  |  Core | Identity | Accounts | Resellers | HostingPlans | Servers | Websites ...  |  |
|  +----------------------------------------+----------------------------------------+  |
|                                           |                                           |
|  +----------------------------------------v----------------------------------------+  |
|  |                      DOMAIN SERVICES & ENTITLEMENT ENGINE                       |  |
|  |  Quota Validation | Authorization Policies | Action Execution | Audit Logger    |  |
|  +-------------------+--------------------+--------------------+-------------------+  |
|                      |                    |                    |                      |
|                      v                    v                    v                      |
|             +-----------------+  +-----------------+  +-----------------+             |
|             | Database Engine |  |  Redis Cluster  |  | Laravel Queues  |             |
|             | MySQL/MariaDB   |  | Cache & Sessions|  | Async Jobs      |             |
|             +-----------------+  +-----------------+  +--------+--------+             |
+----------------------------------------------------------------|----------------------+
                                                                 |
                           mTLS / gRPC / Encrypted REST RPC      | (Provisioning Queue)
                                                                 v
+---------------------------------------------------------------------------------------+
|                              MANAGED INFRASTRUCTURE NODE                              |
|                                                                                       |
|  +---------------------------------------------------------------------------------+  |
|  |                          VORTAN NODE AGENT (systemd)                            |  |
|  |     Root-level privileged daemon listening strictly on loopback or private VPC   |  |
|  |     Allowlisted Command Handlers | Strict JSON Schema Validation | Audit Log    |  |
|  +----------------------------------------+----------------------------------------+  |
|                                           |                                           |
|  +----------------------------------------v----------------------------------------+  |
|  |                        OPERATING SYSTEM & SYSTEM DAEMONS                        |  |
|  |  User Jails (UID/GID) | Nginx/Apache | PHP-FPM | MariaDB/PostgreSQL | Postfix   |  |
|  |  Dovecot | BIND/PowerDNS | Pure-FTPd | Certbot ACME | Fail2ban | Firewalld      |  |
|  +---------------------------------------------------------------------------------+  |
+---------------------------------------------------------------------------------------+
```

---

## 3. Technology Stack & Trade-Off Analysis

### 3.1 Backend: Laravel 13 & PHP 8.4+
- **PHP 8.4 Support:** Native property hooks, asymmetric visibility, new array functions, and JIT compiler performance enhancements.
- **Laravel 13 Architecture:** Streamlined application bootstrap (`bootstrap/app.php`), native concurrency helpers, enhanced queue batching, and lightweight routing pipelines.

### 3.2 Database Selection: MySQL/MariaDB vs. PostgreSQL
| Evaluation Criteria | MySQL 8.0 / MariaDB 10.11 | PostgreSQL 16+ | Selected Choice & Rationale |
| :--- | :--- | :--- | :--- |
| **Hosting Industry Ubiquity** | Industry standard. Every hosting server natively runs MySQL/MariaDB for customer workloads. | Powerful, but requires a separate server process on default LAMP/LEMP stacks. | **MySQL 8.0 / MariaDB 10.11 (Recommended Primary)**<br>Using MariaDB/MySQL allows single-server deployments to share the database engine without introducing a second daemon, lowering baseline RAM usage by 400MB. |
| **JSON Operations** | Native JSON data type, `JSON_EXTRACT`, indexed virtual columns. | Advanced JSONB with GIN indexing. | MariaDB/MySQL JSON support is more than sufficient for module settings and quota overrides. |
| **Transactional Integrity** | Full ACID with InnoDB. | Full ACID with MVCC. | Both provide bulletproof isolation for multi-tenant account changes. |
| **PostgreSQL Support** | N/A | First-class citizen in Laravel Eloquent. | **VortanCP Decision:** Core schemas are written strictly to database-agnostic standard Laravel migrations. PostgreSQL is supported as an optional drop-in control plane database. |

### 3.3 Frontend Stack: TailAdmin, Livewire, Alpine.js & Tailwind CSS
- **TailAdmin Integration:** TailAdmin provides a polished, production-tested dark/light theme admin design system built on Tailwind CSS.
- **Livewire v4:** Provides real-time reactivity without client-side API boilerplate. Livewire handles server-side data fetching, pagination, sorting, search debounce, and action dispatches.
- **Alpine.js Rule:** Livewire v3/v4 bundles and initializes Alpine.js automatically. **Never load a standalone `alpine.js` CDN script.** Alpine is reserved solely for ephemeral client state:
  - Dropdown toggles
  - Modal open/close transitions
  - Toast auto-dismissal
  - Local tab switching
  - Command palette shortcuts (`Ctrl+K`)
  All business state, quota checks, form validation, and database operations reside strictly in Livewire and backend domain actions.

---

## 4. Role-Based Navigation & Interface Architecture

VortanCP provides three integrated interface perspectives:
1. **Administrator Panel (`/admin`):**
   - Node inventory, server clustering, service health, IP routing, global firewall, reseller management, system updates, global audit trail.
2. **Reseller Panel (`/reseller`):**
   - Reseller customer management, assigned resource pool tracking, package creation within assigned caps, white-label branding, customer support.
3. **Customer Hosting Panel (`/app`):**
   - Subscription switcher, domain/subdomain management, DNS editor, SSL certificates, Web File Manager, databases, mailboxes, PHP settings, WordPress Toolkit, Git deployments, backups.

### 4.1 Dynamic Navigation Engine
Navigation is compiled dynamically from registered modules. Each module registers its menu entries via `NavigationItem` objects declaring:
- Target Panel (`admin`, `reseller`, `customer`)
- Required Permission (e.g. `websites.view`, `services.manage`)
- Required Module Dependency (e.g. visible only if `vortan.wordpress` is enabled)
- Active Route Matcher (for automated highlight state)
- Badge Provider (e.g. dynamic count of pending updates or failed jobs)

---

## 5. Asynchronous Provisioning & State Reconciliation

### 5.1 The Two-Phase Provisioning Pattern
To prevent web timeouts and ensure operational reliability, state mutations follow a strict asynchronous workflow:

1. **Phase 1: Intent & Validation (Synchronous HTTP/Livewire)**
   - Validate form input.
   - Authorize action via Laravel Policy.
   - Verify quotas via `EntitlementService`.
   - Create database record with status `ProvisioningState::Pending`.
   - Dispatch background job to Redis queue (`provisioning` queue).
   - Return immediate UI feedback showing pending spinner.

2. **Phase 2: Execution & Verification (Asynchronous Queue Job)**
   - Worker picks up job.
   - Record updated to `ProvisioningState::Running`.
   - Infrastructure adapter executes RPC call to Vortan Node Agent.
   - Node Agent executes low-level changes (e.g. creates Linux user, writes Nginx vhost, tests config with `nginx -t`, reloads daemon).
   - Node Agent verifies daemon state and returns execution receipt with stdout/stderr.
   - Job marks record as `ProvisioningState::Active`.
   - UI receives Livewire event / polling update and turns green.

### 5.2 Failure & Compensating Transactions
If the Node Agent reports an error (e.g. `nginx -t` failed syntax check):
1. Node Agent automatically restores previous configuration backup.
2. Record status transitions to `ProvisioningState::Failed`.
3. Error payload is stored in `provisioning_operations` table.
4. Administrator/Customer receives an in-app notification with actionable diagnostic guidance.
5. Livewire displays a "Retry Provisioning" or "View Error Log" button.

---

## 6. Real-Time Telemetry & Metric Ingestion

Telemetry collection is decoupled from user requests:
- A lightweight collector cron/daemon on the node reads `/proc/stat`, `/proc/meminfo`, `/proc/loadavg`, and `df -h` every 60 seconds.
- Metrics are pushed in batches or cached in Redis.
- Customer and Admin dashboards query cached telemetry, ensuring page loads render in under 40 milliseconds regardless of server load.
