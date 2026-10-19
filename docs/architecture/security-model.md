# VortanCP Security Model & Multitenancy Architecture

## 1. Threat Model & Security Posture

Hosting control panels represent high-value targets because compromising the control plane can grant root access across entire fleets of servers or expose thousands of customer websites and databases. VortanCP is engineered under the assumption of **defense-in-depth** and **zero-trust component boundaries**.

---

## 2. Authentication & Credential Hardening

### 2.1 Password Security & Storage
- Passwords are strictly hashed using **Argon2id** (`memory_cost=65536`, `time_cost=4`, `threads=1`) or bcrypt (`rounds=12`).
- Minimum password entropy policies enforced (length >= 12, mixed case, numbers, symbols, breached-password check via HaveIBeenPwned API hash-prefix lookup).
- Anti-enumeration: Login responses return identical generic error messages for non-existent users and incorrect passwords.

### 2.2 Multi-Factor Authentication (MFA / 2FA)
- Implements standard RFC 6238 Time-Based One-Time Password (TOTP) algorithm.
- Secret seeds are encrypted at rest in the database using AES-256-GCM via Laravel's native encryption layer.
- Generates 8 one-time cryptographic recovery codes, hashed with SHA-256 before storage in database.
- Rate limiting: Max 5 failed 2FA verification attempts per session before requiring re-authentication.

### 2.3 Session Management & Device Tracking
- Cookie flags: `Secure=true`, `HttpOnly=true`, `SameSite=Lax`, `Path=/`.
- Session fixation protection: `session()->regenerate()` invoked on every login and privilege transition.
- Device fingerprinting: Logs IP, User-Agent, and initial location to `session_histories` table; permits instantaneous one-click revocation of individual sessions or "Logout all other devices".

---

## 3. Role-Based Access Control (RBAC) & Permissions

### 3.1 Role Hierarchy & Permissions Matrix

| Permission Domain | Super Administrator | Server Administrator | Reseller | Hosting Customer | Team Member (Delegated) | Read-Only / Auditor |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Server Nodes & Hardware** | Full Control | Full Control | No Access | No Access | No Access | View Only |
| **System Services (Reload/Restart)**| Full Control | Full Control | No Access | No Access | No Access | View Only |
| **Reseller Accounts & Pools** | Full Control | No Access | View Own Pool | No Access | No Access | View Only |
| **Hosting Account Lifecycle** | Full Control | Full Control | Own Customers Only | No Access | No Access | View Only |
| **Websites & Virtual Hosts** | Full Control | Full Control | Own Customers Only | Own Sites Only | Permitted Sites Only | View Permitted Only |
| **DNS Zones & Records** | Full Control | Full Control | Own Zones Only | Own Zones Only | Permitted Zones Only | View Permitted Only |
| **SSL/TLS Certificates** | Full Control | Full Control | Own Certs Only | Own Certs Only | Permitted Certs Only | View Permitted Only |
| **File Manager & FTP** | Full Control | Full Control | View Customers | Own Files Only | Permitted Dirs Only | No Access |
| **Databases & DB Users** | Full Control | Full Control | View Customers | Own DBs Only | Permitted DBs Only | View Permitted Only |
| **Email Accounts & Mailboxes** | Full Control | Full Control | View Customers | Own Mail Only | Permitted Mail Only | View Permitted Only |
| **Cron Jobs / Scheduled Tasks** | Full Control | Full Control | View Customers | Own Tasks Only | Permitted Tasks Only | View Permitted Only |
| **Audit Logs & Security Events**| Global View | Global View | Own Tenant View | Own Account View | No Access | Global Read-Only |

### 3.2 Authorization Enforcement Strategy
Every request path enforces authorization at three independent layers:
1. **Route Middleware:** Gates access to the panel layout (`auth`, `role:admin`, `role:reseller`, `role:customer`).
2. **Laravel Policies:** Every Eloquent model possesses a dedicated policy (`DomainPolicy`, `DatabasePolicy`, `MailboxPolicy`).
3. **Livewire Component Checks:** Before calling any domain action, Livewire components explicitly invoke:
   `$this->authorize('update', $domain);`
4. **Tenant Scoping in Eloquent:** Eloquent global scopes (`BelongsToCustomerScope`, `BelongsToResellerScope`) automatically constrain queries to the authenticated tenant, rendering Insecure Direct Object References (IDOR) impossible.

---

## 4. Tenant Isolation & Protection Against IDOR

```php
// Example of Tenancy Scope Enforcement in Domain Repository
public function findForCurrentTenant(int $domainId): Domain
{
    $user = auth()->user();
    
    return Domain::query()
        ->when($user->isCustomer(), function ($query) use ($user) {
            $query->whereIn('hosting_account_id', $user->customer->hostingAccounts->pluck('id'));
        })
        ->when($user->isReseller(), function ($query) use ($user) {
            $query->whereIn('hosting_account_id', $user->reseller->allCustomerHostingAccounts()->pluck('id'));
        })
        ->findOrFail($domainId);
}
```

---

## 5. File System & File Manager Sandboxing

To completely prevent Directory Traversal, Symlink Escalation, and Path Injection:
1. **Chroot Path Validation:** Every requested path is resolved via `realpath()`. The resolved canonical path is verified to begin with the customer's allocated directory:
   ```php
   $userRoot = realpath("/home/{$account->username}");
   $targetPath = realpath($userRoot . '/' . ltrim($requestedPath, '/'));
   
   if ($targetPath === false || !str_starts_with($targetPath, $userRoot)) {
       throw new UnauthorizedPathAccessException("Path traversal attempt detected.");
   }
   ```
2. **Archive Extraction Jail:** Extraction of `.zip` or `.tar.gz` files validates every internal entry header before writing, aborting if any path contains `../` or attempts to create symlinks pointing outside the target directory.
3. **MIME-Type & Executable Upload Guards:** Files uploaded via web interface are checked against extension blacklists (`.php`, `.phar`, `.phtml`, `.cgi`, `.sh`) when uploaded outside configured executable paths, preventing unexpected web shell execution in static media directories.

---

## 6. Protection Against Common Web Vulnerabilities

### 6.1 Server-Side Request Forgery (SSRF) Defense
Features that fetch external URLs (Git repository clone URLs, remote backup endpoints, webhook notifications, Let's Encrypt domain validation checks) are protected by a hardened HTTP client:
- DNS resolution is performed prior to connection.
- Private IP ranges (RFC 1918 `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`), loopback (`127.0.0.0/8`), link-local (`169.254.0.0/16`), and AWS metadata endpoints (`169.254.169.254`) are strictly blocked.
- Redirects are validated step-by-step; following redirects to internal IP ranges is prohibited.

### 6.2 Cross-Site Scripting (XSS) & Content Security Policy (CSP)
- Blade templates use automatic HTML entity encoding (`{{ $variable }}`). Raw unescaped output (`{!! !!}`) is forbidden for user-supplied data.
- Strict Content Security Policy header deployed:
  `default-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;` (tailored for Livewire and Alpine).

### 6.3 Command Injection Prevention
- The application never passes user input to shell functions (`exec`, `system`, `shell_exec`, `passthru`, backticks).
- All interactions with the Node Agent pass structured JSON payloads.
- Low-level binaries executed on nodes are invoked via `Symfony\Component\Process\Process` with explicit parameter arrays (bypassing the shell interpreter entirely).

---

## 7. Rate Limiting, Brute Force & Anti-Automation

- **Panel Login:** 5 attempts per minute per IP/Email; triggers 15-minute exponential lockout on breach.
- **API Endpoints:** Rate limited to 60 requests per minute per API token via Redis token bucket.
- **ACME SSL Ordering:** Rate limited to 5 certificates per hour per domain to avoid Let's Encrypt API exhaustion.
- **Fail2ban Integration:** Persistent login failures across Web, SSH, FTP, and IMAP automatically push the offending IP address to kernel-level `ipset` drop rules via the Node Agent.
